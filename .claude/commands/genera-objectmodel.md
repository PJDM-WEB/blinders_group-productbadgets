# /genera-objectmodel

Genera un ObjectModel para PrestaShop 1.7.8.x siguiendo las convenciones del proyecto.

## Uso

```
/genera-objectmodel NombreClase nombre_tabla campo1:tipo campo2:tipo ...
```

Ejemplo:
```
/genera-objectmodel ProductBadge productbadge bg_color:string text_color:string position:string active:bool
```

## Reglas que debe seguir el ObjectModel generado

1. `$definition['table']` usa el nombre de tabla **sin prefijo** (el prefijo lo añade el core).
2. `$definition['primary']` es `id_{nombre_tabla}`.
3. Si hay campos de texto visible al usuario, añadir `multilang: true` y campo con `'lang' => true`.
4. Tipos válidos: `TYPE_STRING`, `TYPE_INT`, `TYPE_BOOL`, `TYPE_DATE`, `TYPE_FLOAT`, `TYPE_HTML`.
5. Todos los campos `TYPE_STRING` deben incluir `'size'` y `'validate'`.
6. Métodos de consulta custom deben usar `DbQuery` encadenado, nunca SQL en string puro.
7. Campos que vienen de input del usuario: castear a `(int)` o validar con regex antes de persistir.
8. Si el módulo tiene una única entidad, definir el ObjectModel directamente en `productbadges.php` (PS lo carga antes que cualquier controlador). Si hay varias entidades, usar `classes/NombreClase.php` con `require_once` explícito en cada controlador que la instancie.
