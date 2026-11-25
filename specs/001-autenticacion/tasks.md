# Tareas · 001 · Autenticación y control de acceso

- [x] **T-AUT-01** · Tabla `usuarios` con rol, área, activo y autorización de datos · Cumple: RF-AUT-01 · Prueba: A13
- [x] **T-AUT-02** · Validaciones `validarDatosUsuario` y `validarPassword` · Cumple: RF-AUT-01 · Prueba: A10, A12, S04
- [x] **T-AUT-03** · Registro que fuerza el rol cliente y exige autorización Ley 1581 · Cumple: RF-AUT-01 · Prueba: A11, A13
- [x] **T-AUT-04** · Login con `password_verify`, rehash y redirección por rol · Cumple: RF-AUT-02, RNF-AUT-01 · Prueba: A01, A02
- [x] **T-AUT-05** · Tabla `intentos_login` y bloqueo de 15 minutos tras 5 fallos · Cumple: RF-AUT-03 · Prueba: A06
- [x] **T-AUT-06** · Logout por POST con CSRF y cierre por inactividad · Cumple: RF-AUT-04 · Prueba: A07
- [x] **T-AUT-07** · `requiereLogin` / `requiereRol` en todas las páginas privadas · Cumple: RF-AUT-05 · Prueba: A08, A09, D05
- [x] **T-AUT-08** · `session_regenerate_id` y cookie segura · Cumple: RF-AUT-06 · Prueba: A03, A04
- [x] **T-AUT-09** · Releer el usuario desde la BD en cada petición · Cumple: RF-AUT-07 · Prueba: D02
