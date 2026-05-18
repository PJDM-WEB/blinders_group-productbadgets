# /revisa-hooks

Revisa que todos los hooks registrados en `productbadges.php` (método `install()`) existen como hooks estándar en PrestaShop 1.7.8.x y están correctamente implementados.

## Pasos

1. Lee el array de hooks en `install()` dentro de `modules/productbadges/productbadges.php`.
2. Para cada hook, verifica que:
   - Existe en la lista de hooks verificados del CLAUDE.md.
   - El método `hook{NombreHook}()` está implementado en la misma clase.
   - El método comprueba `PRODUCTBADGES_ENABLED` antes de actuar.
   - Los parámetros del hook (`$params`) se acceden con comprobación de existencia (`?? 0`).
3. Verifica que `hookDisplayHeader` solo carga CSS en los controladores relevantes (no en todas las páginas).
4. Informa de cualquier hook registrado que no esté implementado o que no exista en PS 1.7.8.x.
