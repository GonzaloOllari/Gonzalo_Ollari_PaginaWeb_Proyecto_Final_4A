Trabajo realizado por Gonzalo Ollari | Sagrado Corazon | 4A | Trabajo Practico Final | 2026
Cuenta Admin: admin@taller.com | Admin1234  (cambiar la clave despues de entrar)

INSTALACION
1. Copiar la carpeta a C:\xampp\htdocs\ e iniciar Apache y MySQL en XAMPP.
2. En phpMyAdmin importar taller_mecanico.sql (crea la base "taller_mecanico").
3. Abrir http://localhost/<carpeta>/login.php

ARCHIVOS
conexion.php           Conexion PDO, sesion y funciones comunes (CSRF, roles, validaciones)
registro.php           Registro publico de usuarios
login.php              Inicio de sesion
cerrar_sesion.php      Cierre de sesion
panel.php              Panel personal (datos del usuario logueado)
admin_usuarios.php     Listado de activos, buscador y alta de usuarios (solo admin)
editar_usuario.php     Edicion de datos de un usuario (solo admin)
archivar_usuario.php   Archivado de cuentas, solo por POST (solo admin)
usuarios_archivados.php Listado de archivados con buscador (solo admin)
reactivar_usuario.php  Reactivacion de cuentas, solo por POST (solo admin)
admin.php              Gestion del taller: turnos, vehiculos, mensajes (solo admin)
index / turnos / contacto / comprobante .php   Funciones propias del taller
estilos.css            Estilos
taller_mecanico.sql    Base de datos y datos iniciales

SEGURIDAD
- Contrasenas con password_hash() / password_verify()
- Consultas preparadas con PDO
- Token CSRF en todos los formularios POST
- Paginas privadas verifican sesion; las de admin verifican el rol en cada pedido (se lee de la base)
- Si una cuenta se archiva, su sesion abierta deja de funcionar
- Salida escapada con htmlspecialchars (funcion e())
- Archivar y reactivar solo aceptan POST; un admin no puede archivarse ni cambiarse el rol a si mismo
