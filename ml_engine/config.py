import os
import sys
from dotenv import load_dotenv
from sqlalchemy import create_engine

# Definir la ruta BASE_DIR y ROOT_DIR
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.dirname(BASE_DIR)

# Cargar el archivo .env desde la raiz
dotenv_path = os.path.join(ROOT_DIR, '.env')

if not os.path.exists(dotenv_path):
    print(f"Advertencia: No se encontró el archivo .env en: {dotenv_path}. Usando valores por defecto.")
else:
    load_dotenv(dotenv_path)


# Obtener credenciales
DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_NAME = os.getenv('DB_NAME', 'db_disciplinar_mx')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '') 

# Crear motor de conexion y exportar ROOT_DIR
def get_db_engine():
    """Retorna la conexión SQLAlchemy lista para usarse con Pandas."""
    try:
        # Usando mysql+mysqlconnector
        connection_str = f"mysql+mysqlconnector://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}?charset=utf8mb4"
        
        engine = create_engine(
            connection_str, 
            pool_recycle=3600, 
            echo=False
        )
        return engine
    except Exception as e:
        print(f"Error configurando conexión a BD: {e}")
        return None

# Exportar ROOT_DIR (para que lo use test_connection.py y mostrar la ruta)
# Agregar en el dashboard los alumnos con problemas mayores primero y despues los que no tengan problemas evidentes
# Agregar una explicacion para comprender el funcionamiento, uso y resultados de la IA
# Ver la forma de agregar graficas