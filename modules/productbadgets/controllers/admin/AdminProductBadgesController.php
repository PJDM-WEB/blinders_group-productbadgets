<?php
/**
 * controllers/admin/AdminProductBadgesController.php
 *
 * Controlador de back office para la gestión de badges.
 * Hereda de ModuleAdminController para separar la lógica de back office
 * del archivo principal del módulo.
 *
 * La clase ProductBadge (ObjectModel) está definida en productbadges.php,
 * que PS 1.7 carga antes que cualquier controlador del módulo.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminProductBadgesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table       = 'productbadge';
        $this->className   = 'ProductBadge';
        $this->lang        = true;      // Activa soporte multilenguaje en HelperList/HelperForm
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected badges?'),
                'icon'    => 'icon-trash',
            ],
        ];

        // Columnas del listado
        $this->fields_list = [
            'id_badge'   => ['title' => $this->l('ID'),       'align' => 'center', 'class' => 'fixed-width-xs'],
            'badge_text' => ['title' => $this->l('Text'),     'lang'  => true],
            'position'   => ['title' => $this->l('Position')],
            'active'     => ['title' => $this->l('Active'),   'active' => 'status', 'type' => 'bool', 'align' => 'center'],
        ];

        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // Formulario de creación / edición
    // -------------------------------------------------------------------------

    public function renderForm(): string
    {
        /** @var ProductBadge|null $object */
        $object = $this->object;

        // Productos ya asignados a esta badge (para el campo multiple-select)
        $linkedProducts = [];
        if ($object && $object->id) {
            $linkedProducts = $object->getLinkedProducts();
        }

        // Lista de productos activos para el selector
        $products = Product::getSimpleProducts($this->context->language->id);

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Badge'),
                'icon'  => 'icon-tag',
            ],
            'input' => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Badge text'),
                    'name'     => 'badge_text',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Visible text on the badge. Supports all active languages.'),
                ],
                [
                    'type'    => 'color',
                    'label'   => $this->l('Background color'),
                    'name'    => 'bg_color',
                    'hint'    => $this->l('CSS hex color, e.g. #FF0000'),
                ],
                [
                    'type'    => 'color',
                    'label'   => $this->l('Text color'),
                    'name'    => 'text_color',
                    'hint'    => $this->l('CSS hex color, e.g. #FFFFFF'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Position'),
                    'name'    => 'position',
                    'options' => [
                        'query' => [
                            ['id' => 'top-left',  'name' => $this->l('Top left')],
                            ['id' => 'top-right', 'name' => $this->l('Top right')],
                        ],
                        'id'   => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Active'),
                    'name'    => 'active',
                    'values'  => [
                        ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Assigned products'),
                    'name'    => 'assigned_products[]',
                    'id'      => 'assigned_products',
                    'multiple' => true,
                    'hint'    => $this->l('Hold Ctrl / Cmd to select multiple.'),
                    'options' => [
                        'query'   => $products,
                        'id'      => 'id_product',
                        'name'    => 'name',
                        'default' => [
                            'value' => '',
                            'label' => $this->l('-- None --'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        // Pre-selecciona los productos ya vinculados
        if (!empty($linkedProducts)) {
            // Marcamos los valores en el objeto para que HelperForm los pre-seleccione
            $this->tpl_form_vars['assigned_products'] = $linkedProducts;
        }

        return parent::renderForm();
    }

    // -------------------------------------------------------------------------
    // Persistencia
    // -------------------------------------------------------------------------

    /**
     * Sobreescribe postProcess para gestionar la relación con productos
     * después de guardar el ObjectModel.
     */
    public function postProcess(): void
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            // Validación adicional de colores antes de guardar
            $bgColor   = Tools::getValue('bg_color');
            $textColor = Tools::getValue('text_color');

            if (!$this->isValidHexColor($bgColor)) {
                $this->errors[] = $this->l('Background color must be a valid hex color (e.g. #FF0000).');
            }
            if (!$this->isValidHexColor($textColor)) {
                $this->errors[] = $this->l('Text color must be a valid hex color (e.g. #FFFFFF).');
            }

            // Validar posición
            $position = Tools::getValue('position');
            if (!in_array($position, ['top-left', 'top-right'], true)) {
                $this->errors[] = $this->l('Invalid position value.');
            }

            if (!empty($this->errors)) {
                return;
            }
        }

        parent::postProcess();

        // Tras guardar el ObjectModel, sincronizamos los productos asignados
        if ($this->object && $this->object->id && Tools::isSubmit('submitAdd' . $this->table)) {
            $rawIds     = Tools::getValue('assigned_products', []);
            $productIds = is_array($rawIds) ? array_map('intval', $rawIds) : [];

            $this->object->setLinkedProducts($productIds);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Valida que un string sea un color hexadecimal CSS.
     */
    private function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', trim($color));
    }
}
