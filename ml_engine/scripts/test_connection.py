import sys
import os
import pandas as pd
from sqlalchemy import text

sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from config import get_db_engine, ROOT_DIR

def test_system():
    print(f"Raíz del proyecto detectada: {ROOT_DIR}")
    
    engine = get_db_engine()
    
    if engine:
        try:
            with engine.connect() as conn:
                # Prueba simple
                result = conn.execute(text("SELECT DATABASE();"))
                db_actual = result.scalar()
                print(f"Conexion exitosa a la Base de Datos: '{db_actual}'")
                
                # Prueba con Pandas
                print("Probando lectura con Pandas...")
                df = pd.read_sql("SHOW TABLES", conn)
                print(f"   -> Se encontraron {len(df)} tablas en el sistema.")
                print("   -> Tablas:", df.iloc[:,0].tolist())
                
        except Exception as e:
            print(f"Error al conectar a MySQL: {e}")
    else:
        print("No se pudo crear el motor de base de datos.")

if __name__ == "__main__":
    test_system()