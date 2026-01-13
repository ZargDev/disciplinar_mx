import pandas as pd
import numpy as np
import joblib
import sys
import os
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report

sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from config import get_db_engine

def entrenar():
    engine = get_db_engine()
    print(">>> Extrayendo datos de SQL...")
    
    query = """
    SELECT 
        a.id as alumno_id,
        a.tiene_diagnostico_formal,
        a.toma_medicacion,
        e.fecha_incidente,
        e.intensidad,
        e.nivel_gravedad,
        cc.nombre as conducta,
        cc.peso_riesgo, 
        ea.rol_incidente,
        e.descripcion_hechos
    FROM alumnos a
    JOIN evaluacion_alumnos ea ON a.id = ea.alumno_id
    JOIN evaluacion_conducta e ON ea.evaluacion_id = e.id
    LEFT JOIN evaluacion_categorias ec ON e.id = ec.evaluacion_id
    LEFT JOIN categorias_conducta cc ON ec.categoria_id = cc.id
    WHERE e.fecha_incidente >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    """
    
    df_raw = pd.read_sql(query, engine)
    
    if df_raw.empty:
        print("No hay datos suficientes para iniciar el entrenamiento.")
        return

    print(">>> Feature Engineering...")
    
    perfiles = []
    
    # DICCIONARIO NLP
    nlp_weights = {
        'td_markers': {
            'palabras': ['crueldad', 'arma', 'navaja', 'sangre', 'robo', 'sexual', 'matar', 'quemar', 'ilegal'],
            'peso': 0.95
        },
        'tnd_markers': {
            'palabras': ['venganza', 'rencor', 'desafío', 'molesta', 'discute', 'grita', 'niega', 'reglas'],
            'peso': 0.80
        },
        'tdah_markers': {
            'palabras': ['interrumpe', 'motor', 'mueve', 'pierde', 'olvida', 'distraído', 'nubes', 'inquieto'],
            'peso': 0.60
        }
    }

    for alumno_id, grupo in df_raw.groupby('alumno_id'):
        data = {'alumno_id': alumno_id}
        
        # 1. Variables Temporales
        fechas = pd.to_datetime(grupo['fecha_incidente'])
        data['dias_desde_primer_incidente'] = (fechas.max() - fechas.min()).days
        data['total_incidentes'] = grupo['fecha_incidente'].nunique()
        
        # 2. Variables de Intensidad
        grupo['peso_riesgo'] = grupo['peso_riesgo'].fillna(1)
        data['intensidad_promedio'] = grupo['intensidad'].mean()
        data['gravedad_ponderada_total'] = (grupo['intensidad'] * grupo['peso_riesgo']).sum()
        
        # 3. Variables Médicas (Integra el historial clínico)
        data['factor_medico'] = float(grupo['tiene_diagnostico_formal'].max()) # 1.0 si tiene, 0.0 si no
        data['factor_medicacion'] = float(grupo['toma_medicacion'].max())
        
        # 4. Análisis de Texto (NLP)
        texto_completo = " ".join(grupo['conducta'].fillna('').astype(str)) + " " + \
                         " ".join(grupo['descripcion_hechos'].fillna('').astype(str))
        texto_completo = texto_completo.lower()
        
        data['score_nlp_td'] = sum(texto_completo.count(w) * nlp_weights['td_markers']['peso'] for w in nlp_weights['td_markers']['palabras'])
        data['score_nlp_tnd'] = sum(texto_completo.count(w) * nlp_weights['tnd_markers']['peso'] for w in nlp_weights['tnd_markers']['palabras'])
        data['score_nlp_tdah'] = sum(texto_completo.count(w) * nlp_weights['tdah_markers']['peso'] for w in nlp_weights['tdah_markers']['palabras'])
        
        # 5. Roles
        roles = " ".join(grupo['rol_incidente'].fillna('').astype(str)).lower()
        data['rol_agresor_ratio'] = roles.count('agresor') / len(grupo)
        
        # --- ETIQUETADO AUTOMÁTICO (GROUND TRUTH) ---
        diagnostico = 'Sin Riesgo'
        
        # Reglas ajustadas: Si hay diagnóstico médico previo, el umbral de alerta baja
        if (data['score_nlp_td'] >= 1.5) or (data['gravedad_ponderada_total'] > 15 and data['rol_agresor_ratio'] > 0.6):
            diagnostico = 'Riesgo TD'
            
        elif (data['score_nlp_tnd'] >= 2.0) or (data['factor_medico'] == 1.0 and data['score_nlp_tnd'] > 1.0):
            diagnostico = 'Riesgo TND'
            
        elif (data['score_nlp_tdah'] >= 3.0) or (data['factor_medico'] == 1.0 and data['score_nlp_tdah'] > 1.5):
            diagnostico = 'Riesgo TDAH'
            
        data['target'] = diagnostico
        perfiles.append(data)

    df_features = pd.DataFrame(perfiles)
    
    features_cols = ['dias_desde_primer_incidente', 'total_incidentes', 'intensidad_promedio', 
                     'gravedad_ponderada_total', 'score_nlp_td', 'score_nlp_tnd', 
                     'score_nlp_tdah', 'rol_agresor_ratio', 'factor_medico', 'factor_medicacion']
    
    X = df_features[features_cols].fillna(0)
    y = df_features['target']
    
    print(f"\n>>> Entrenando Random Forest con {len(X)} perfiles...")
    
    # MODELO: RANDOM FOREST
    # n_estimators=100: Equilibrio entre velocidad y precisión
    # class_weight='balanced': Vital para detectar casos raros (como el TDAH que es minoría)
    clf = RandomForestClassifier(n_estimators=100, class_weight='balanced', random_state=42)
    clf.fit(X, y)
    
    # Guardado
    path_modelo = os.path.join(os.path.dirname(__file__), '../models/modelo_disciplina_v1.pkl')
    joblib.dump(clf, path_modelo)
    
    print(f"Modelo guardado exitosamente en: {path_modelo}")
    print("\n--- REPORTE FINAL DE RENDIMIENTO ---")
    print(classification_report(y, clf.predict(X)))

if __name__ == "__main__":
    entrenar()