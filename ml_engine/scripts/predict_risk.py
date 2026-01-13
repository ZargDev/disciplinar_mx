import pandas as pd
import joblib
import json
import sys
import os
import traceback

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

def get_db_engine():
    try:
        from sqlalchemy import create_engine
        DB_CONFIG = {
            'host': 'localhost',
            'database': 'db_disciplinar_mx',
            'user': 'root',
            'password': 'AMZdv.21636',
            'port': 3306
        }
        connection_string = (
            f"mysql+mysqlconnector://{DB_CONFIG['user']}:{DB_CONFIG['password']}"
            f"@{DB_CONFIG['host']}:{DB_CONFIG['port']}/{DB_CONFIG['database']}"
        )
        return create_engine(connection_string)
    except Exception as e:
        print(json.dumps({'error': True, 'mensaje': f'Error conexion BD: {str(e)}'}))
        return None

def obtener_caracteristicas_alumno(alumno_id, engine):
    """Lógica unificada (Debe coincidir con Training)"""
    try:
        query = f"""
        SELECT 
            a.tiene_diagnostico_formal,
            a.toma_medicacion,
            e.fecha_incidente,
            e.intensidad,
            cc.nombre as conducta,
            cc.peso_riesgo,
            e.descripcion_hechos,
            ea.rol_incidente
        FROM alumnos a
        JOIN evaluacion_alumnos ea ON a.id = ea.alumno_id
        JOIN evaluacion_conducta e ON ea.evaluacion_id = e.id
        LEFT JOIN evaluacion_categorias ec ON e.id = ec.evaluacion_id
        LEFT JOIN categorias_conducta cc ON ec.categoria_id = cc.id
        WHERE a.id = {alumno_id}
        AND e.fecha_incidente >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
        """
        
        df = pd.read_sql(query, engine)
        
        # Estructura base
        base_features = {
            'dias_desde_primer_incidente': 0, 'total_incidentes': 0, 'intensidad_promedio': 0.0,
            'gravedad_ponderada_total': 0.0, 'score_nlp_td': 0.0, 'score_nlp_tnd': 0.0,
            'score_nlp_tdah': 0.0, 'rol_agresor_ratio': 0.0,
            'factor_medico': 0.0, 'factor_medicacion': 0.0
        }
        
        if df.empty:
            # Si no hay reportes, aun debemos revisar si tiene datos médicos en la tabla alumnos
            # (Esta lógica extra se omite por simplicidad, asumiendo que sin reportes no se evalúa riesgo conductual)
            return base_features

        df['fecha_incidente'] = pd.to_datetime(df['fecha_incidente'])
        df['peso_riesgo'] = df['peso_riesgo'].fillna(1)
        
        # Numéricas
        base_features['dias_desde_primer_incidente'] = int((df['fecha_incidente'].max() - df['fecha_incidente'].min()).days)
        base_features['total_incidentes'] = int(df['fecha_incidente'].nunique())
        base_features['intensidad_promedio'] = float(df['intensidad'].mean())
        base_features['gravedad_ponderada_total'] = float((df['intensidad'] * df['peso_riesgo']).sum())
        
        # Médicas (NUEVO)
        base_features['factor_medico'] = float(df['tiene_diagnostico_formal'].max())
        base_features['factor_medicacion'] = float(df['toma_medicacion'].max())

        # NLP (Diccionario Unificado)
        texto = " ".join(df['conducta'].fillna('').astype(str)) + " " + \
                " ".join(df['descripcion_hechos'].fillna('').astype(str))
        texto = texto.lower()
        
        nlp_td = ['crueldad', 'arma', 'navaja', 'sangre', 'robo', 'sexual', 'matar', 'quemar', 'ilegal']
        nlp_tnd = ['venganza', 'rencor', 'desafío', 'molesta', 'discute', 'grita', 'niega', 'reglas']
        nlp_tdah = ['interrumpe', 'motor', 'mueve', 'pierde', 'olvida', 'distraído', 'nubes', 'inquieto']
        
        base_features['score_nlp_td'] = sum(texto.count(w) * 0.95 for w in nlp_td)
        base_features['score_nlp_tnd'] = sum(texto.count(w) * 0.80 for w in nlp_tnd)
        base_features['score_nlp_tdah'] = sum(texto.count(w) * 0.60 for w in nlp_tdah)
        
        roles = " ".join(df['rol_incidente'].fillna('').astype(str)).lower()
        base_features['rol_agresor_ratio'] = float(roles.count('agresor') / len(df))
        
        return base_features
        
    except Exception as e:
        print(json.dumps({'error': True, 'mensaje': f'Error features: {str(e)}'}))
        return None

def predecir(alumno_id):
    try:
        engine = get_db_engine()
        if engine is None: return
            
        script_dir = os.path.dirname(os.path.abspath(__file__))
        modelo_path = os.path.join(script_dir, '..', 'models', 'modelo_disciplina_v1.pkl')
        
        if not os.path.exists(modelo_path):
            print(json.dumps({'error': True, 'mensaje': 'Modelo no encontrado'}))
            return
            
        modelo = joblib.load(modelo_path)
        features = obtener_caracteristicas_alumno(alumno_id, engine)
        
        if features is None or features['total_incidentes'] == 0:
            print(json.dumps({'error': False, 'riesgo_detectado': 'Sin Riesgo', 'probabilidad': 0.0}))
            return
            
        # Orden EXACTO al del entrenamiento
        feature_cols = [
            'dias_desde_primer_incidente', 'total_incidentes', 'intensidad_promedio', 
            'gravedad_ponderada_total', 'score_nlp_td', 'score_nlp_tnd', 
            'score_nlp_tdah', 'rol_agresor_ratio', 'factor_medico', 'factor_medicacion'
        ]
        
        X_pred = pd.DataFrame([{col: features.get(col, 0) for col in feature_cols}])
        
        prediccion = modelo.predict(X_pred)[0]
        probabilidades = modelo.predict_proba(X_pred)[0]
        clases = list(modelo.classes_)
        prob_riesgo = probabilidades[clases.index(prediccion)] * 100
        
        resultado = {
            'error': False,
            'alumno_id': alumno_id,
            'riesgo_detectado': str(prediccion),
            'probabilidad': round(float(prob_riesgo), 2),
            'caracteristicas': features
        }
        print(json.dumps(resultado, ensure_ascii=False))
        
    except Exception as e:
        print(json.dumps({'error': True, 'mensaje': f'Error fatal: {str(e)}'}))

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({'error': True, 'mensaje': 'Falta ID'}))
    else:
        predecir(int(sys.argv[1]))