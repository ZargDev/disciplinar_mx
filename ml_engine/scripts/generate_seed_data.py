import pandas as pd
import random
from faker import Faker
from datetime import datetime, timedelta
from sqlalchemy import text
import sys
import os

sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from config import get_db_engine

fake = Faker('es_MX')
engine = get_db_engine()

def generar_datos():
    print("LIMPIEZA DE BASE DE DATOS")
    with engine.connect() as conn:
        conn.execute(text("SET FOREIGN_KEY_CHECKS=0;"))
        conn.execute(text("TRUNCATE TABLE intervenciones;"))
        conn.execute(text("TRUNCATE TABLE alertas;"))
        conn.execute(text("TRUNCATE TABLE evaluacion_detonantes;"))
        conn.execute(text("TRUNCATE TABLE evaluacion_categorias;"))
        conn.execute(text("TRUNCATE TABLE evaluacion_alumnos;"))
        conn.execute(text("TRUNCATE TABLE evaluacion_conducta;"))
        conn.execute(text("TRUNCATE TABLE alumnos;"))
        conn.execute(text("TRUNCATE TABLE tutores;"))
        conn.execute(text("TRUNCATE TABLE docentes;"))
        conn.execute(text("TRUNCATE TABLE asignaturas;"))
        conn.execute(text("TRUNCATE TABLE categorias_conducta;"))
        conn.execute(text("TRUNCATE TABLE detonantes_conducta;"))
        
        conn.execute(text("SET FOREIGN_KEY_CHECKS=1;"))
        conn.commit()

    print("POBLANDO CATALOGOS")
    
    # Asignaturas
    asignaturas = [
        {'nombre': 'Matemáticas', 'area_academica': 'Ciencias Exactas', 'grado_target': 'General'},
        {'nombre': 'Español / Literatura', 'area_academica': 'Lenguaje', 'grado_target': 'General'},
        {'nombre': 'Historia Universal', 'area_academica': 'Ciencias Sociales', 'grado_target': 'General'},
        {'nombre': 'Ciencias (Biología/Física)', 'area_academica': 'Ciencias Naturales', 'grado_target': 'General'},
        {'nombre': 'Inglés', 'area_academica': 'Lenguas Extranjeras', 'grado_target': 'General'},
        {'nombre': 'Educación Física', 'area_academica': 'Deportes', 'grado_target': 'General'},
        {'nombre': 'Formación Cívica y Ética', 'area_academica': 'Humanidades', 'grado_target': 'General'},
        {'nombre': 'Artes', 'area_academica': 'Artística', 'grado_target': 'General'}
    ]
    pd.DataFrame(asignaturas).to_sql('asignaturas', engine, if_exists='append', index=False)
    
    # Docentes
    docentes = []
    for _ in range(10):
        docentes.append({
            'nombre': fake.first_name(),
            'apellido_paterno': fake.last_name(),
            'apellido_materno': fake.last_name(),
            'especialidad': random.choice(['Lic. Educación', 'Ingeniero', 'Psicólogo', 'Lic. Letras']),
            'email_institucional': fake.email(),
            'estado': 'Activo'
        })
    pd.DataFrame(docentes).to_sql('docentes', engine, if_exists='append', index=False)

    # Categorias y detonantes
    categorias = [
        {'id': 1, 'nombre': 'Inatención', 'descripcion': 'Dificultad para mantener el foco', 'peso_riesgo': 1},
        {'id': 2, 'nombre': 'Hiperactividad', 'descripcion': 'Movimiento excesivo', 'peso_riesgo': 1},
        {'id': 3, 'nombre': 'Impulsividad', 'descripcion': 'Actuar sin pensar', 'peso_riesgo': 2},
        {'id': 4, 'nombre': 'Desafío a Autoridad', 'descripcion': 'Negativa a seguir reglas', 'peso_riesgo': 3},
        {'id': 5, 'nombre': 'Agresión Verbal', 'descripcion': 'Insultos o gritos', 'peso_riesgo': 3},
        {'id': 6, 'nombre': 'Agresión Física', 'descripcion': 'Golpes o empujones', 'peso_riesgo': 5},
        {'id': 7, 'nombre': 'Destrucción Propiedad', 'descripcion': 'Vandalismo', 'peso_riesgo': 4},
        {'id': 8, 'nombre': 'Ciberbullying', 'descripcion': 'Acoso digital', 'peso_riesgo': 4}
    ]
    pd.DataFrame(categorias).to_sql('categorias_conducta', engine, if_exists='append', index=False)

    detonantes = [
        {'id': 1, 'nombre': 'Sin detonante aparente', 'tipo': 'Interno'},
        {'id': 2, 'nombre': 'Llamada de atención', 'tipo': 'Social'},
        {'id': 3, 'nombre': 'Interacción con pares', 'tipo': 'Social'},
        {'id': 4, 'nombre': 'Frustración académica', 'tipo': 'Académico'},
        {'id': 5, 'nombre': 'Ruido/Estímulo sensorial', 'tipo': 'Ambiental'},
        {'id': 6, 'nombre': 'Negación de permiso', 'tipo': 'Social'}
    ]
    pd.DataFrame(detonantes).to_sql('detonantes_conducta', engine, if_exists='append', index=False)

    print("CREANDO 200 ALUMNOS Y TUTORES")
    
    arquetipos = ['NORMAL'] * 140 + ['TDAH'] * 20 + ['TND'] * 20 + ['TD'] * 20
    random.shuffle(arquetipos)
    
    alumnos_ids_map = []
    
    with engine.connect() as conn:
        for i in range(200):
            # Crear tutor
            nombre_tutor = fake.first_name()
            ap_pat_tutor = fake.last_name()
            ap_mat_tutor = fake.last_name()
            
            # Insertar tutor
            result_tutor = conn.execute(text("""
                INSERT INTO tutores (nombre, apellido_paterno, apellido_materno, telefono_contacto, email)
                VALUES (:nom, :app, :apm, :tel, :email)
            """), {
                'nom': nombre_tutor, 'app': ap_pat_tutor, 'apm': ap_mat_tutor,
                'tel': fake.phone_number(), 'email': fake.email()
            })
            tutor_id = result_tutor.lastrowid

            # Crear alumno
            sexo = random.choice(['M', 'F'])
            nombre_alum = fake.first_name_male() if sexo == 'M' else fake.first_name_female()
            
            # Insertar alumno
            result_alum = conn.execute(text("""
                INSERT INTO alumnos (tutor_principal_id, nombre, apellido_paterno, apellido_materno, genero, fecha_nacimiento, grado, grupo, estado)
                VALUES (:tid, :nom, :app, :apm, :gen, :nac, :gra, :gru, 'Activo')
            """), {
                'tid': tutor_id,
                'nom': nombre_alum,
                'app': ap_pat_tutor,
                'apm': fake.last_name(),
                'gen': sexo,
                'nac': fake.date_of_birth(minimum_age=12, maximum_age=16),
                'gra': random.choice(['1', '2', '3']),
                'gru': random.choice(['A', 'B', 'C', 'D'])
            })
            alumno_id = result_alum.lastrowid
            
            alumnos_ids_map.append({'id': alumno_id, 'arquetipo': arquetipos[i]})
        
        conn.commit()

    print("GENERANDO EVALUACIONES DE CONDUCTA")
    
    evaluaciones = []
    rel_cat = []
    rel_det = []
    rel_alum = []
    
    eval_id_counter = 1
    
    # Acciones 
    acc_leves = ["Amonestación verbal", "Nota en bitácora", "Cambio de lugar", "Plática reflexiva"]
    acc_mod = ["Reporte escrito", "Citatorio a padres", "Trabajo social en receso", "Retiro de celular temporal"]
    acc_graves = ["Suspensión (1-3 días)", "Carta compromiso", "Canalización psicológica", "Consejo técnico"]

    for item in alumnos_ids_map:
        aid = item['id']
        arq = item['arquetipo']
        
        # Cantidad de incidentes
        n_inc = 0
        if arq == 'NORMAL': n_inc = random.choices([0, 1, 2], weights=[60, 30, 10])[0]
        elif arq == 'TDAH': n_inc = random.randint(3, 8)
        elif arq == 'TND': n_inc = random.randint(4, 10)
        elif arq == 'TD': n_inc = random.randint(2, 6)

        for _ in range(n_inc):
            # Datos base
            fecha = fake.date_time_between(start_date='-10M', end_date='now')
            docente = random.randint(1, 10)
            materia = random.randint(1, 8)
            actividad = random.choice(['Clase Magistral', 'Trabajo Grupo', 'Recreo', 'Transicion'])
            
            # Logica de transtornos
            if arq == 'TDAH':
                gravedad = random.choice(['Leve', 'Moderada'])
                intensidad = random.randint(1, 3)
                cats_ids = [1, 2] 
                det_id = random.choice([1, 5])
                desc = random.choice([
                    "Se distrae con facilidad y pierde sus materiales.",
                    "Uso de celular en clase reiterado.",
                    "Interrumpe al docente constantemente.",
                    "Se levanta de su lugar sin permiso."
                ])
            elif arq == 'TND':
                gravedad = random.choice(['Moderada', 'Grave'])
                intensidad = random.randint(2, 4)
                cats_ids = [4, 5]
                det_id = random.choice([2, 6])
                desc = random.choice([
                    "Contesta de forma agresiva al docente.",
                    "Se niega a realizar la actividad propuesta.",
                    "Molesta a sus compañeros intencionalmente.",
                    "Azota la puerta al salir del salón."
                ])
            elif arq == 'TD':
                gravedad = random.choice(['Grave', 'Critica'])
                intensidad = random.randint(4, 5)
                cats_ids = [6, 7, 8]
                det_id = 3
                desc = random.choice([
                    "Golpeó a un compañero durante el receso.",
                    "Dañó el mobiliario escolar (pupitre).",
                    "Amenazó a un compañero para quitarle el lunch.",
                    "Fue sorprendido con sustancias prohibidas (vape)."
                ])
            else:
                gravedad = 'Leve'
                intensidad = 1
                cats_ids = [1]
                det_id = 3
                desc = "Platicando en clase, distracción menor."

            # Accion tomada
            if gravedad == 'Leve': accion = random.choice(acc_leves)
            elif gravedad == 'Moderada': accion = random.choice(acc_mod)
            else: accion = random.choice(acc_graves)

            # Agregar evaluacion
            evaluaciones.append({
                'docente_id': docente,
                'asignatura_id': materia,
                'fecha_incidente': fecha,
                'actividad_momento': actividad,
                'nivel_gravedad': gravedad,
                'intensidad': intensidad,
                'descripcion_hechos': desc,
                'accion_tomada': accion
            })
            
            # Relaciones
            cat_sel = random.choice(cats_ids)
            rel_cat.append({'evaluacion_id': eval_id_counter, 'categoria_id': cat_sel})
            rel_det.append({'evaluacion_id': eval_id_counter, 'detonante_id': det_id})
            
            rol = 'Agresor' if arq in ['TND', 'TD'] else 'Participante'
            rel_alum.append({'evaluacion_id': eval_id_counter, 'alumno_id': aid, 'rol_incidente': rol})

            eval_id_counter += 1

    # Insertar datos
    pd.DataFrame(evaluaciones).to_sql('evaluacion_conducta', engine, if_exists='append', index=False)
    pd.DataFrame(rel_cat).to_sql('evaluacion_categorias', engine, if_exists='append', index=False)
    pd.DataFrame(rel_det).to_sql('evaluacion_detonantes', engine, if_exists='append', index=False)
    pd.DataFrame(rel_alum).to_sql('evaluacion_alumnos', engine, if_exists='append', index=False)

    print(f"SE CREARON 200 ALUMNOS Y {eval_id_counter-1} REPORTES.")

if __name__ == "__main__":
    generar_datos()