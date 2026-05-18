# CLAUDE.md — productbadges

Instrucciones de proyecto para Claude Code CLI y cualquier agente de IA que trabaje en este repositorio.

## Stack de referencia

- **PrestaShop**: 1.7.8.11
- **PHP**: 8.1
- **Motor BD**: MySQL 5.7 / MariaDB 10.4
- **Motor de plantillas**: Smarty 3 (integrado en PS 1.7)
- **jQuery**: versión que incluye PrestaShop (no añadir librerías externas)

## Reglas del proyecto

### PHP
- Compatibilidad PHP 7.4 y 8.1. No usar sintaxis exclusiva de 8.0+ (named args, fibers, enums) sin comprobar compat.
- Siempre castear a `(int)` los IDs que vengan de `Tools::getValue()` o de arrays de `$_GET`/`$_POST`.
- Colores CSS: validar con regex `/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/` antes de persistir o pasar a la vista.
- Nunca concatenar directamente variables en cláusulas `ORDER BY`. Usar siempre whitelist de columnas.
- Texto de usuario: escapar con `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` en PHP; con `|escape:'html':'UTF-8'` en Smarty.

### PrestaShop APIs
- **Queries**: usar `DbQuery` con métodos encadenados o `Db::getInstance()->executeS()`. Nunca `mysql_query`.
- **Configuración**: `Configuration::get()` / `Configuration::updateValue()` / `Configuration::deleteByName()`.
- **Textos**: `$this->l('string')` en PHP, `{l s='string' mod='productbadges'}` en Smarty.
- **Assets**: cargar CSS/JS solo donde se necesiten mediante `hookDisplayHeader` con comprobación del tipo de controlador.
- **ObjectModel**: usar `$definition` con tipos y validadores. Nunca acceder directamente a la BD desde el modelo salvo métodos específicos documentados.
- **AdminController**: toda lógica de back office en `ModuleAdminController`, nunca en `productbadges.php`.

### Hooks disponibles en PS 1.7.8.x (verificados)
- `displayProductListItem` — listado de categoría
- `displayProductCover` — ficha de producto (imagen principal)
- `displayHeader` — `<head>` de todas las páginas
- `displayHome` — página de inicio (si el tema lo soporta)
- `displaySearch` — resultados de búsqueda

**NO usar**: `displayProductPrice` (no existe en 1.7.8.x), `displayProductButtons` (existe pero no es el indicado para badges visuales sobre imagen).

### Autoloader
El autoloader de PS 1.7 **no** resuelve clases en `modules/nombre/classes/`. Para módulos con una única entidad, definir el ObjectModel directamente en `productbadges.php` (que PS carga antes que cualquier controlador del módulo) elimina la necesidad de `require_once`. Si el módulo crece y aparecen más entidades, moverlas a `classes/` y añadir `require_once` explícito en cada controlador que las use.

### Multitienda
- No filtrar por `id_shop` en las tablas del módulo (badges son globales por diseño).
- Las llamadas a `Configuration` usan el contexto activo automáticamente; no forzar `id_shop` manualmente.

### Sin Composer
No añadir `composer.json` ni dependencias externas. Cualquier utilidad necesaria debe implementarse con las APIs del core de PrestaShop o PHP nativo.

## Estructura de ficheros

```
modules/productbadges/
├── productbadges.php          ← ProductBadge (ObjectModel) + ProductBadges (Module) + hooks
├── config.xml
├── sql/
│   ├── install.php            ← Array $sql con CREATE TABLE
│   └── uninstall.php          ← Array $sql con DROP TABLE
├── controllers/admin/
│   └── AdminProductBadgesController.php  ← Todo el CRUD de badges
├── views/
│   ├── templates/hook/badges.tpl
│   └── css/productbadges.css
└── translations/
    ├── es.php
    └── en.php
```

