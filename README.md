# Comprobantes NCF
## _Instalación_
1. Entrar en la carpeta de instalación de Dolibarr dentro del servidor
2. Descomprimir el paquete dentro de la carpeta '/htdocs/'.
3. Agregamos las nuevas plantillas PDF a sus respectivos módulos (las plantillas se encuentran en `carpetadeinstalacion/htdocs/ncf/doc`).
  a) Copiamos el archivo que se encuentra en 'carpetadeinstalacion/htdocs/ncf/doc/cliente' y lo pegamos dentro de carpetadeinstalacion/htdocs/core/modules/facture/doc/
  b) Copiamos el archivo que se encuentra en 'carpetadeinstalacion/htdocs/ncf/doc/proveedor' y lo pegamos dentro de carpetadeinstalacion/htdocs/core/modules/supplier_invoice/doc/
4. Entrar a las configuraciones del módulo de facturas y del módulo de proveedores para activar la plantilla modificada de cada uno de los módulos y marcarla como predeterminada.
5. Activar el módulo desde las configuraciones de Dolibarr

## _Módulos que deben estar activados para el correcto funcionamiento_
1. Facturas
2. Vendedores / Proveedores
3. Terceros
4. Bancos y cajas
5. Productos
6. Servicios
