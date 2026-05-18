# Product Badges — Módulo PrestaShop 1.7.8.x

Módulo para gestionar etiquetas visuales reutilizables ("badges") sobre las imágenes de los productos del catálogo.

## Instalación

1. Copia la carpeta `modules/productbadges/` en el directorio `modules/` de tu instalación de PrestaShop 1.7.
2. Ve a **Back Office → Módulos → Gestión de módulos**, busca "Product Badges" e instálalo.
3. El módulo creará automáticamente sus tablas en base de datos y registrará los hooks necesarios.
4. Accede a **Catálogo → Product Badges** para crear y asignar etiquetas.

## Versión probada

- PrestaShop: **1.7.8.11**
- PHP: **8.1**
- MySQL: 5.7 / MariaDB 10.4

## Estructura del módulo

```
modules/productbadges/
├── productbadges.php          ← Clase principal, hooks, configuración
├── config.xml
├── logo.png
├── sql/
│   ├── install.php
│   └── uninstall.php
├── controllers/
│   └── admin/
│       └── AdminProductBadgesController.php
├── views/
│   ├── templates/hook/
│   │   └── badges.tpl
│   ├── css/productbadges.css
│   └── js/                    ← vacío; sin JS externo necesario
└── translations/
    ├── es.php
    └── en.php
```
## Decisiones técnicas

### Separación back office / módulo principal
La lógica de gestión (CRUD de badges, asignación a productos) vive íntegramente en `AdminProductBadgesController`, que hereda de `ModuleAdminController`. El archivo `productbadges.php` solo gestiona instalación, object model, configuración global y hooks de frontend.

### Sanitización y escapado
- Colores validados con regex `/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/` tanto en el controlador como en el helper de `productbadges.php`.
- Textos escapados con `htmlspecialchars` en PHP antes de pasarlos a Smarty, y con `|escape:'html':'UTF-8'` en las plantillas (doble escaping deliberado para cubrir salidas directas en atributos `style`).
- Posición validada contra whitelist `['top-left', 'top-right']`.
- `ORDER BY` en consultas de listado validado contra whitelist de columnas.
- Relación producto usa `array_map('intval', ...)` antes de insertar.

### ObjectModel
Se usa `ObjectModel` con `multilang: true` para aprovechar las tablas `_lang` gestionadas por el core. Esto integra correctamente el campo `badge_text` con el sistema de idiomas de PrestaShop.

### Carga de assets
CSS cargado solo en las páginas relevantes (categoría, ficha de producto, home, búsqueda) mediante comprobación de tipo de controlador en `hookDisplayHeader`. Sin JS externo más allá del jQuery que PrestaShop ya proporciona.

### Multitienda
El módulo no filtra por `id_shop` en sus tablas (badges son globales). Esto es coherente con el requisito de "no romper en multitienda sin que sea obligatorio diferenciar por tienda". Las llamadas a `Configuration::get/updateValue` usan el contexto activo de PrestaShop, que gestiona correctamente el scope de tienda.

### Sin Composer
No se requieren dependencias externas. Todo el código usa las APIs nativas de PrestaShop 1.7.

## Lo que quedó fuera (y por qué)

- **Tests unitarios**: no son eliminatorios según el enunciado. Con más tiempo habría añadido PHPUnit para `ProductBadge::sanitizeColor()` y la validación de colores.
- **Previsualización de color en tiempo real**: requeriría JS adicional; descartado para mantener el módulo sin librerías externas.
- **Filtrado por tienda en badges**: el enunciado lo marca como no obligatorio; la arquitectura actual lo permitiría añadiendo `id_shop` a `productbadge_product`.

## Asunciones

- Se asume que el tema activo soporta los hooks `displayProductListItem` y `displayProductCover`. El tema Classic de PS 1.7 los incluye.
- La imagen del producto en el frontend tiene `position: relative` (o el contenedor padre). El CSS del módulo usa `position: absolute` para las badges.
