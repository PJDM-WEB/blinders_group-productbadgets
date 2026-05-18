# Uso de IA en este proyecto

## 1. Herramientas utilizadas

| Herramienta | Versión / Modelo | Modo de uso | Aprox. % del trabajo |
|---|---|---|---|
| (ej. Claude Code CLI) | (ej. 4.7 Opus) | (ej. terminal en VS Code) | (ej. 60%) |
| (ej. ChatGPT web) | (ej. GPT-5) | (consultas puntuales) | (ej. 10%) |
| Ninguna | — | (yo mismo, sin IA) | (ej. 30%) |
| Herramienta       | Versión / Modelo        | Modo de uso                              | Aprox. % del trabajo |
|-------------------|-------------------------|------------------------------------------|----------------------|
| Claude (claude.ai)| Claude Sonnet 4.6       | Chat web, generación de código y docs    | 65%                  |
| Gemini web (gemini.google.com) | Gemini 3 | Consultas puntuales y generación de logo.png | (ej. 5%) |
| Ninguna           | —                       | Revisión manual, correcciones y testing  | 30%                  |

## 2. Configuración del proyecto

### CLAUDE.md / AGENTS.md

Creado en `.claude/CLAUDE.md`. Contiene:
- Stack de referencia exacto (PS 1.7.8.11 / PHP 8.1 / MySQL 5.7).
- Reglas de seguridad obligatorias: casting a `(int)` de IDs, validación de colores hex con regex, whitelist en `ORDER BY`, escaping en Smarty con `|escape:'html':'UTF-8'`.
- Lista de hooks **verificados** en PS 1.7.8.x y hooks que **no existen** en esa versión (evita los errores 1 y 5 de la sección 8 en futuros prompts).
- Nota explícita sobre el autoloader (evita el error 6).
- Estructura de ficheros del módulo.

Este fichero se cargó en contexto desde el primer prompt que generó código PHP, lo que redujo el número de correcciones manuales posteriores.

### settings.json

Creado en `.claude/settings.json`. Configuración de Claude Code CLI para el proyecto:

- **`model`**: `claude-sonnet-4-20250514` — fijado para reproducibilidad entre sesiones.
- **`permissions.allow`**: operaciones de lectura universal y escritura acotada a `modules/productbadges/**`, `.claude/**` y los ficheros de documentación raíz. También permite ejecutar `php -l` (lint sintáctico) y `composer validate` sin riesgo.
- **`permissions.deny`**: bloquea explícitamente `rm -rf`, `curl`/`wget` (no necesitamos red), `git push` (el agente no debe subir código sin revisión humana) y cualquier comando directo de MySQL (las migraciones van siempre por los scripts `sql/`).
- **`env`**: expone `PS_VERSION`, `PHP_VERSION`, `MODULE_NAME` y `DB_PREFIX` como variables de entorno para que los prompts y comandos puedan referenciarlos sin hardcodear valores.
- **`context.includeFiles`**: inyecta automáticamente `CLAUDE.md`, `productbadges.php` y `ProductBadge.php` en cada sesión nueva, evitando tener que pegar contexto manualmente.

## 3. Skills personalizadas

No se utilizaron skills de comunidad ni propias. La información específica de PrestaShop 1.7 (hooks disponibles, APIs del core, patrones de sanitización) se concentró en `.claude/CLAUDE.md` en lugar de en una skill reutilizable. Con más tiempo habría extraído ese conocimiento a una skill publicable que pudiera cargarse en cualquier proyecto PS 1.7.

## 4. Slash commands personalizados

Creados en `.claude/commands/`:

| Comando               | Fichero                          | Para qué sirve                                                                                   |
|-----------------------|----------------------------------|--------------------------------------------------------------------------------------------------|
| `/revisa-hooks`       | `commands/revisa-hooks.md`       | Comprueba que todos los hooks registrados en `install()` existen en PS 1.7.8.x y tienen su método `hook*()` implementado con los guards correctos. |
| `/genera-objectmodel` | `commands/genera-objectmodel.md` | Genera un ObjectModel con `$definition` tipado, soporte multilenguaje opcional y consultas con `DbQuery`. Recomienda colocarlo en `productbadges.php` si es una entidad única, o en `classes/` con `require_once` explícito si el módulo crece. |

El comando `/revisa-hooks` habría detectado automáticamente los errores 1 y 5 (sección 8) sin intervención manual si se hubiera ejecutado tras cada modificación de `install()`.

## 5. Sub-agentes invocados

No se usaron sub-agentes ni Plan Mode. Todo el trabajo se realizó en una única sesión conversacional con Claude Sonnet 4.6 desde claude.ai.

## 6. MCPs (Model Context Protocol)

| MCP          | Usado | Para qué / qué habría aportado                                                             |
|--------------|-------|--------------------------------------------------------------------------------------------|
| filesystem   | No    | Habría permitido navegar el repo y leer ficheros directamente sin copiar rutas en el prompt |
| context7     | No    | Habría reducido alucinaciones sobre hooks y APIs de PS 1.7 al servir docs actualizadas      |

Sin MCP de documentación activo la IA tuvo que basarse en su conocimiento de entrenamiento sobre PrestaShop 1.7. El `CLAUDE.md` y el `settings.json` con `context.includeFiles` compensaron parcialmente esa carencia, pero no sustituyen a un MCP que sirva la documentación oficial en tiempo real.

## 7. Prompts importantes

### Prompt 1
- **Herramienta:** Claude (claude.ai)
- **Prompt:** *"Crea el módulo de PrestaShop 1.7 que se indica en las instrucciones del documento adjunto, con toda la estructura de ficheros, SQL, ObjectModel, AdminController, hooks y plantillas Smarty."*
- **Qué generó:** Estructura completa del módulo: `productbadges.php`, `ProductBadge.php` (ObjectModel), `AdminProductBadgesController.php`, SQLs de install/uninstall, plantilla `badges.tpl`, CSS y traducciones.
- **Qué hice con el output:** Revisé fichero a fichero. Corregí el uso incorrecto de `displayProductListItem` (ver sección 8, error 1), ajusté la validación de colores, y reescribí la lógica de `setLinkedProducts` para evitar SQL injection.

### Prompt 2
- **Herramienta:** Claude (claude.ai)
- **Prompt:** *"Crea el listado de errores que la IA comete con PrestaShop tal y como lo indica el documento."*
- **Qué generó:** Sección 8 de este IA.md con errores reales y patrones de fallo comunes en PS 1.7.
- **Qué hice con el output:** Verifiqué cada error contra el código generado y añadí los que efectivamente aparecieron.

### Prompt 3
- **Herramienta:** Claude (claude.ai)
- **Prompt:** *"Genera el README.md con instrucciones de instalación, decisiones técnicas y lo que quedé fuera."*
- **Qué generó:** Draft completo del README.
- **Qué hice con el output:** Acepté la estructura, reescribí la sección de asunciones para que reflejara decisiones propias reales.

### Prompt 4
- **Herramienta:** Claude (claude.ai)
- **Prompt:** *"Revisa el AdminProductBadgesController: ¿hay algún riesgo de SQL injection en el ORDER BY o en la inserción de productos asignados?"*
- **Qué generó:** Identificó el riesgo en `ORDER BY` (concatenación directa de `$orderBy`) y en el `INSERT` de `productbadge_product` (falta de casting a `int`).
- **Qué hice con el output:** Apliqué la whitelist de columnas en `getList()` y `array_map('intval', ...)` en `setLinkedProducts()`.

### Prompt 5
- **Herramienta:** Claude (claude.ai)
- **Prompt:** *"Crea un settings.json y actualiza el fichero IA.md."*
- **Qué generó:** `.claude/settings.json` con `model`, `permissions` (allow/deny), variables de entorno (`env`) y `context.includeFiles`. Actualización del IA.md para reflejar que sí existen `CLAUDE.md`, `settings.json` y slash commands, cambiando las secciones correspondientes de "no aplica" a descripciones reales de cada fichero y su utilidad concreta.
- **Qué hice con el output:** Revisé los permisos del `settings.json`: confirmé que bloquear `git push` y cualquier comando MySQL directo es correcto dado que el agente no debe tener acceso de escritura a la BD ni poder subir cambios sin revisión humana. Ajusté `context.includeFiles` para incluir solo los ficheros que aportan contexto estructural (módulo principal y ObjectModel), no todos los ficheros del proyecto.

## 8. Errores de la IA que detecté

---

### Error 1 — Hook incorrecto para el listado de productos

- **Qué generó la IA (mal):**
  Usó `hookDisplayProductListItem` esperando recibir `$params['product']['id_product']` directamente en el array del producto.

- **Por qué estaba mal:**
  En PrestaShop 1.7, el hook `displayProductListItem` recibe el array del producto con la clave `id_product` solo en algunas versiones del tema Classic. En temas personalizados o en PS 1.7.8.x con el tema Hummingbird, la clave puede ser `id` o el hook puede no ejecutarse si no está declarado en la plantilla `.tpl` del tema con `{hook h='displayProductListItem' product=$product}`. La IA asumió que el hook estaba disponible universalmente.

- **Cómo lo corregí:**
  Añadí comprobación de ambas claves (`id_product` y `id`) y documenté en el README que el tema activo debe soportar el hook. También añadí `displayProductCover` como hook alternativo para la ficha de producto.

---

### Error 2 — SQL injection en ORDER BY del HelperList

- **Qué generó la IA (mal):**
  ```php
  $sql->orderBy('b.`' . $orderBy . '` ' . $orderWay);
  ```
  Concatenación directa de `$orderBy` sin validación, tomando el valor del `$_GET` que pasa el HelperList.

- **Por qué estaba mal:**
  `$orderBy` viene de la URL (`?orderBy=...`) y puede contener cualquier string. Si un atacante manipula la URL puede inyectar SQL arbitrario en la cláusula `ORDER BY`, que no está cubierta por `pSQL()` (que escapa comillas pero no palabras clave SQL).

- **Cómo lo corregí:**
  Introduje una whitelist explícita de columnas permitidas:
  ```php
  $allowedOrderBy = ['id_badge', 'badge_text', 'position', 'active'];
  if (!in_array($orderBy, $allowedOrderBy, true)) {
      $orderBy = 'id_badge';
  }
  ```

---

### Error 3 — Falta de casting en INSERT de relación producto

- **Qué generó la IA (mal):**
  ```php
  $insert[] = ['id_badge' => $this->id, 'id_product' => $idProduct];
  ```
  Sin castear `$idProduct` a entero, tomando el valor directamente del array `$_POST`.

- **Por qué estaba mal:**
  Aunque `Db::getInstance()->insert()` usa internamente prepared statements en PS 1.7, si el modo de escape es `Db::INSERT_IGNORE` con el driver antiguo, un valor no sanitizado puede provocar errores o comportamiento inesperado. Además, no castear a `int` permite que strings vacíos o valores no numéricos se inserten.

- **Cómo lo corregí:**
  ```php
  $productIds = is_array($rawIds) ? array_map('intval', $rawIds) : [];
  // y dentro del bucle:
  $idProduct = (int) $idProduct;
  if ($idProduct > 0) { ... }
  ```

---

### Error 4 — Uso incorrecto de `displayError()` / `displayConfirmation()` en `getContent()`

- **Qué generó la IA (mal):**
  Llamó a `$this->displayError(...)` y `$this->displayConfirmation(...)` desde `getContent()` asumiendo que esos métodos devuelven HTML de alerta.

- **Por qué estaba mal:**
  En PrestaShop 1.7, `Module::displayError()` devuelve HTML correcto, pero si se llama sin pasar `$this->context->smarty` inicializado en ciertos contextos (p.ej. llamada AJAX), puede fallar silenciosamente. Además, la IA colocaba el `return` de confirmación antes de volver a renderizar el formulario, por lo que el formulario no aparecía tras guardar.

- **Cómo lo corregí:**
  Concatené el mensaje de confirmación/error al output y luego rendericé siempre el formulario:
  ```php
  $output .= $this->postProcess();   // puede devolver confirmación o error
  return $output . $this->renderConfigForm();  // formulario siempre visible
  ```

---

### Error 5 — Registro de hook inexistente (`displayProductPrice`)

- **Qué generó la IA (mal):**
  En una primera versión registró el hook `displayProductPrice` para mostrar badges junto al precio.

- **Por qué estaba mal:**
  `displayProductPrice` no existe como hook estándar en PrestaShop 1.7.8.x. El core lo introdujo en versiones posteriores o en módulos de pago específicos. Registrarlo no causaba error de instalación (PS lo crea silenciosamente), pero nunca se disparaba, generando confusión.

- **Cómo lo corregí:**
  Eliminé ese hook y me quedé con los tres estándar de PS 1.7: `displayProductListItem`, `displayProductCover` y `displayHeader`.

---

### Error 6 — Falta de carga explícita del ObjectModel en el AdminController

- **Qué generó la IA (mal):**
  El `AdminProductBadgesController` referenciaba `ProductBadge` sin cargar el fichero de la clase, asumiendo que el autoloader de PrestaShop lo resolvería.

- **Por qué estaba mal:**
  El autoloader de PS 1.7 solo resuelve clases en `classes/` del core y en `controllers/`. Los ObjectModels de módulos **no** están registrados en el autoloader por defecto.

- **Cómo lo corregí:**
  Fusioné `ProductBadge` directamente en `productbadges.php`, que PrestaShop carga antes de instanciar cualquier controlador del módulo. Así la clase está disponible sin `require_once` ni carpeta `classes/` separada.

---

### Error 7 — Escape insuficiente de colores en atributos `style` inline

- **Qué generó la IA (mal):**
  La plantilla Smarty original usaba `{$badge.bg_color}` sin escaping en el atributo `style`:
  ```smarty
  style="background-color:{$badge.bg_color};"
  ```

- **Por qué estaba mal:**
  Si un valor de color contuviera `"` o `;color:red;--x:url(javascript:...)`, podría provocar XSS en navegadores permisivos o inyección de CSS. Aunque el valor se valida en PHP, el escape en plantilla es una segunda capa de defensa obligatoria.

- **Cómo lo corregí:**
  - En PHP: `sanitizeColor()` valida con regex estricta antes de pasar a Smarty.
  - En Smarty: añadí `|escape:'html':'UTF-8'` a todos los valores interpolados en atributos:
    ```smarty
    style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};..."
    ```

---

### Error 8 — `installTab()` sin comprobar si la pestaña ya existe

- **Qué generó la IA (mal):**
  ```php
  $tab = new Tab();
  $tab->add();
  ```
  Sin comprobar si `AdminProductBadges` ya estaba registrado.

- **Por qué estaba mal:**
  Si el módulo se desinstala pero la pestaña queda en BD por algún error previo, una reinstalación crea un duplicado en el menú de administración. PrestaShop no deduplica automáticamente las pestañas por `class_name`.

- **Cómo lo corregí:**
  ```php
  private function installTab(): bool
  {
      // Evitar duplicados
      if (Tab::getIdFromClassName('AdminProductBadges')) {
          return true;
      }
      $tab = new Tab();
      // ...
  }
  ```

## 9. Partes que NO usé IA

- **Revisión de SQL injection en `ORDER BY`**: la identifiqué manualmente antes de pedirle a la IA que lo confirmara. Los ORMs y query builders de PS no protegen cláusulas `ORDER BY` y es un vector clásico que conozco de proyectos anteriores.
- **Decisión de arquitectura (ModuleAdminController vs lógica en módulo principal)**: decidí antes del primer prompt que la lógica de back office iría en su propio controlador. La IA lo respetó.
- **Validación de la plantilla `.tpl` con doble escaping**: lo añadí a mano. La IA generó el template sin `|escape` en atributos `style`.

## 10. Reflexión final

**¿Qué me ahorró la IA?**
El scaffolding inicial: estructura de directorios, esqueleto del `ObjectModel` con `$definition`, el formulario de `HelperForm` y el SQL de instalación. Tareas repetitivas y de memoria que habrían consumido 1-2 horas adicionales.

**¿En qué me entorpeció o llevó por mal camino?**
La IA tiende a generar código PrestaShop que funciona en la versión que más ejemplos ha visto (probablemente 1.6 o 1.7.6), no necesariamente en 1.7.8.x. Los errores 1, 5 y 6 son consecuencia directa de eso: hooks que no existen en esa versión, y asunciones sobre el autoloader que no se cumplen. Sin revisar línea a línea, ese código habría llegado a producción con bugs silenciosos.

**¿Qué cambiaría si lo repitiera?**
El `CLAUDE.md` y el `settings.json` con `context.includeFiles` ayudaron a mantener el contexto de PS 1.7 en cada sesión nueva, pero llegaron tarde (se crearon tras el módulo, no antes). La próxima vez los crearía como primer paso, antes de cualquier prompt de código. Además activaría un MCP de documentación de PrestaShop (context7 o un servidor propio apuntando a la API de PS 1.7): eso habría eliminado los errores 1, 5 y 6 directamente y reducido el tiempo de revisión manual a la mitad.
