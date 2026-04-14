# GA4 Data Warehouse ETL Tool (PHP) 

Este proyecto es una suite de herramientas ETL (Extract, Transform, Load) diseñada para extraer datos granulares de **Google Analytics 4** y sincronizarlos con un Data Warehouse local (MySQL).

##  Características Avanzadas

### 1. Gestión Óptima de Memoria
Capaz de procesar datasets de más de 200.000 filas (como el funnel de reservas) mediante:
- **Paginación automática** de la API de Google (batches de 50k).
- **Recolección de basura manual** (`gc_collect_cycles`) y liberación de RAM (`unset`) para evitar desbordamientos en procesos de larga duración.

### 2. Integridad de Datos y Ratios Locales
A diferencia de otros conectores, este script extrae métricas aditivas y **calcula los ratios (Bounce Rate, Conversion Rate) localmente** tras la agregación. Esto garantiza que los porcentajes sean exactos sin importar el nivel de agrupación (dispositivo, país o fecha).

### 3. Sincronización Eficiente (Upsert)
Utiliza la lógica `ON DUPLICATE KEY UPDATE` para permitir re-ejecuciones de históricos sin duplicar datos, manteniendo el Data Warehouse siempre actualizado y consistente.

## 🛠️ Stack Tecnológico
- **Lenguaje:** PHP 8.1+
- **API:** Google Analytics Data API v1beta
- **Base de Datos:** MySQL / MariaDB
- **Librerías:** Google Cloud Client Library

##  Instalación
1. Clonar el repositorio.
2. Ejecutar `composer install`.
3. Renombrar `config/config.example.php` a `config.php` y colocar tu `service-account.json` en la carpeta raíz (asegúrate de que esté ignorado por Git).
4. Ejecutar vía CLI.