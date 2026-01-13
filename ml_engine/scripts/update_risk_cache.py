import pandas as pd
import joblib
import json
import sys
import os
import datetime
from sqlalchemy import text

# Configuración de rutas
script_dir = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(script_dir, '..', 'models', 'modelo_disciplina_v1.pkl') 

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
        print(json.dumps({'error': True, 'mensaje': f'Error conexión BD: {str(e)}'}))
        return None

def obtener_caracteristicas_alumno(alumno_id, engine):
    """Lógica unificada de Feature Engineering"""
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
        
        base_features = {
            'dias_desde_primer_incidente': 0, 'total_incidentes': 0, 'intensidad_promedio': 0.0,
            'gravedad_ponderada_total': 0.0, 'score_nlp_td': 0.0, 'score_nlp_tnd': 0.0,
            'score_nlp_tdah': 0.0, 'rol_agresor_ratio': 0.0,
            'factor_medico': 0.0, 'factor_medicacion': 0.0
        }
        
        if df.empty:
            return base_features

        df['fecha_incidente'] = pd.to_datetime(df['fecha_incidente'])
        df['peso_riesgo'] = df['peso_riesgo'].fillna(1)
        
        base_features['dias_desde_primer_incidente'] = int((df['fecha_incidente'].max() - df['fecha_incidente'].min()).days)
        base_features['total_incidentes'] = int(df['fecha_incidente'].nunique())
        base_features['intensidad_promedio'] = float(df['intensidad'].mean())
        base_features['gravedad_ponderada_total'] = float((df['intensidad'] * df['peso_riesgo']).sum())
        base_features['factor_medico'] = float(df['tiene_diagnostico_formal'].max())
        base_features['factor_medicacion'] = float(df['toma_medicacion'].max())
        
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
    except:
        return None

def actualizar_masivo():
    try:
        engine = get_db_engine()
        
        if not os.path.exists(MODEL_PATH):
            print(json.dumps({'error': True, 'mensaje': f'Modelo no encontrado en {MODEL_PATH}'}))
            return

        modelo = joblib.load(MODEL_PATH)
        alumnos_ids = pd.read_sql("SELECT id FROM alumnos WHERE estado = 'Activo'", engine)
        
        feature_cols = [
            'dias_desde_primer_incidente', 'total_incidentes', 'intensidad_promedio', 
            'gravedad_ponderada_total', 'score_nlp_td', 'score_nlp_tnd', 
            'score_nlp_tdah', 'rol_agresor_ratio', 'factor_medico', 'factor_medicacion'
        ]
        
        updates_count = 0
        fecha_actual = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        with engine.connect() as conn:
            for _, row in alumnos_ids.iterrows():
                aid = int(row['id']) 
                feats = obtener_caracteristicas_alumno(aid, engine)
                
                if feats:
                    X = pd.DataFrame([{col: feats.get(col, 0) for col in feature_cols}])
                    
                    pred_raw = modelo.predict(X)[0]
                    riesgo = str(pred_raw)
                    
                    probs = modelo.predict_proba(X)[0]
                    clases = modelo.classes_
                    prob_val = probs[list(clases).index(pred_raw)] * 100
                    
                    # Logica de descuento por buen comportamiento
                    sql_last = text("SELECT DATEDIFF(NOW(), MAX(e.fecha_incidente)) FROM evaluacion_conducta e JOIN evaluacion_alumnos ea ON e.id = ea.evaluacion_id WHERE ea.alumno_id = :aid")
                    dias_sin_reportes = conn.execute(sql_last, {'aid': aid}).scalar() or 0
                    
                    factor_reduccion = 1.0
                    if dias_sin_reportes > 90:
                        factor_reduccion = 0.2
                        if "Riesgo" in riesgo: riesgo = "Riesgo Latente (Inactivo)"
                    elif dias_sin_reportes > 45:
                        factor_reduccion = 0.5
                    
                    prob_val = prob_val * factor_reduccion
                    prob = float(prob_val)

                    sql = text("UPDATE alumnos SET riesgo_ia_cache = :riesgo, probabilidad_ia_cache = :prob, fecha_cache_actualizacion = :fecha WHERE id = :id")
                    conn.execute(sql, {'riesgo': riesgo, 'prob': prob, 'fecha': fecha_actual, 'id': aid})
                    
                    # Alertas Automáticas
                    if prob > 80.0 and ("TND" in riesgo or "TD" in riesgo):
                        check_active = text("SELECT count(*) FROM alertas WHERE alumno_id = :aid AND estado IN ('Abierta', 'En Proceso')")
                        if conn.execute(check_active, {'aid': aid}).scalar() == 0:
                            insert_alerta = text("INSERT INTO alertas (alumno_id, tipo_alerta, riesgo_detectado, probabilidad_riesgo, prioridad, titulo, descripcion, estado, fecha_alerta) VALUES (:aid, 'AI', :riesgo, :prob, 'Alta', 'Detección Automática', 'Patrón crítico detectado por IA.', 'Abierta', NOW())")
                            conn.execute(insert_alerta, {'aid': aid, 'riesgo': riesgo, 'prob': prob})
                    
                    updates_count += 1
            conn.commit()

        print(json.dumps({'success': True, 'mensaje': f'Se actualizaron {updates_count} alumnos.'}))

    except Exception as e:
        import traceback
        print(json.dumps({'error': True, 'mensaje': str(e), 'trace': traceback.format_exc()}))

if __name__ == "__main__":
    actualizar_masivo()