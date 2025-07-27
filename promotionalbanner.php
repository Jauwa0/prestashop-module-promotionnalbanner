<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PromotionalBanner\Helper\BannerConfigHelper;

class Promotionalbanner extends Module
{
    //<editor-fold desc="Declaration of variables">

    // - Hooks list -
    private const REGISTER_HOOKS = [
        'displayHome',
        'actionAdminControllerSetMedia',
    ];

    // - Form component labels -
    private const PROMO_BANNER_TITLE = 'PROMO_BANNER_TITLE';
    private const PROMO_BANNER_TEXT = 'PROMO_BANNER_TEXT';
    private const PROMO_BANNER_IMG = 'PROMO_BANNER_IMG';
    private const PROMO_BANNER_LINK = 'PROMO_BANNER_LINK';

    //</editor-fold>


    public function __construct()
    {
        $this->name    = 'promotionalbanner'; // Technique id
        $this->tab     = 'advertising_marketing'; // Modules catalog section
        $this->version = '1.0.0';
        $this->author  = 'Aurelien DAVID';
        $this->need_instance = 0; // No unnecessary load in BO
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true; // Bootstrap-based templates

        $this->displayName = $this->trans('Promotional Banner', [], 'Modules.Promotionalbanner.Admin');
        $this->description = $this->trans("Displays a promotional banner on the website's homepage. Configuration: Title, test, image, and display duration.", [], 'Modules.Promotionalbanner.Admin');
        // Exemple avec variable
        //$this->trans('Order %order% has been processed', ['%order%' => $orderId], 'Modules.MyModule.Admin');

        parent::__construct();
    }

    public function install(): bool
    {
        // Error in PS configuration
        if(!parent::install()) {
            $this->_errors[] = $this->trans('It seems that a problem in your store is preventing the module from being installed.', [], 'Modules.Promotionalbanner.Admin');
            return false;
        }

        // Controlling Register Hooks
        if(!$this->registerHooks()){
            // "Il semble qu'un problème dans le registre des Hooks empêche le bon fonctionnement du module."
            $this->_errors[] = $this->trans('It seems that an issue in the Hooks registry is preventing the module from working properly.', [], 'Modules.Promotionalbanner.Admin');
            return false;
        }

        // Initialization of ps_configuration table
        if(!$this->defineDefaultConfig()) {
            $this->_errors[] = $this->trans('DB error while initialising default configuration.', [], 'Modules.Promotionalbanner.Admin');
            return false;
        }

        // Adding permissions on the banner images folder
        @chmod(_PS_MODULE_DIR_.$this->name.'/img', 0755);

        return true;
    }

    public function uninstall(): bool
    {
        // Clean ps_configuration table
        if(!$this->cleanConfig()) {
            $this->_errors[] = $this->trans('DB error while cleaning configuration.', [], 'Modules.Promotionalbanner.Admin');
            return false;
        }

        // Deletes the disk images if it exists
        $this->deleteAllImages();

        // Error in PS configuration
        if(!parent::uninstall()) {
            $this->_errors[] = $this->trans('It seems that there is an issue in your store that is preventing the module from being uninstalled.', [], 'Modules.Promotionalbanner.Admin');
            return false;
        }

        return true;
    }


    /**
     * Initialize default config
     * @return bool
     */
    public function defineDefaultConfig(): bool
    {
        try {

            // Initialize ps_configuration table
            Configuration::updateValue(self::PROMO_BANNER_TITLE, '');
            Configuration::updateValue(self::PROMO_BANNER_TEXT, '');
            Configuration::updateValue(self::PROMO_BANNER_IMG, '');

        }
        catch (\PrestaShopException $e) {
            // Database unreachable or request blocked
            PrestaShopLogger::addLog(
                'DB error while initialising default configuration.',
                PrestaShopLoggerCore::LOG_SEVERITY_LEVEL_ERROR,
                0,
                'Module',
                (int) $this->id
            );
            return false;
        }

        return true;
    }

    /**
     * Clean configuration in ps_configuration
     * @return bool
     */
    public function cleanConfig(): bool
    {
        try {

            // Clean ps_configuration table
            Configuration::deleteByName(self::PROMO_BANNER_TITLE);
            Configuration::deleteByName(self::PROMO_BANNER_TEXT);
            Configuration::deleteByName(self::PROMO_BANNER_IMG);

        }
        catch (\PrestaShopException $e) {
            // Database unreachable or request blocked
            PrestaShopLogger::addLog(
                'DB error while cleaning configuration.',
                PrestaShopLoggerCore::LOG_SEVERITY_LEVEL_ERROR,
                0,
                'Module',
                (int) $this->id
            );
            return false;
        }

        return true;
    }


    // -------------------
    // ----- EDITION -----
    // -------------------

    /**
     * Method of handling user editing of the module.
     * @return string
     * @throws Exception
     */
    public function getContent(): string
    {
        $html = '';

        // --- Submission form ---

        if (Tools::isSubmit('submitPromotionalBanner')) {

            // --- Sanitize + Save values in database ---

            // - Title -
            try{
                $sanitizeTitle = BannerConfigHelper::getValidString(Tools::getValue(self::PROMO_BANNER_TITLE), 150); // Limit size - SEO / UI
                Configuration::updateValue(self::PROMO_BANNER_TITLE, $sanitizeTitle);
            }
            catch (\Exception $e) {
                $html .= $this->displayError($this->trans($e->getMessage()));
            }


            // - Text -
            try{
                $sanitizeText = BannerConfigHelper::getValidString(Tools::getValue(self::PROMO_BANNER_TEXT, 250)); // Limit size - SEO / UI
                Configuration::updateValue(self::PROMO_BANNER_TEXT, $sanitizeText);
            }
            catch (\Exception $e) {
                $html .= $this->displayError($this->trans($e->getMessage()));
            }


            // - Image -
            if (isset($_FILES[self::PROMO_BANNER_IMG]) && !empty($_FILES[self::PROMO_BANNER_IMG]['tmp_name'])) {

                try{
                    //$sanitizeImgPath = BannerConfigHelper::getValidImg($_FILES[self::PROMO_BANNER_IMG], _PS_MODULE_DIR_.$this->name.'/img/');
                    $sanitizeImgPath = BannerConfigHelper::getValidImg($_FILES[self::PROMO_BANNER_IMG], _PS_MODULE_DIR_.$this->name.'/img/');

                    // Delete old image
                    $this->deleteCurrentImage();

                    // - Copy file in destination directory -
                    if (!copy($_FILES[self::PROMO_BANNER_IMG]['tmp_name'], $sanitizeImgPath)) { // Just a "copy" because PS still uses it internally after

                        $sanitizeImgPath = "";

                        $html .= $this->displayError($this->trans('Image saving failed.', [], 'Modules.Promotionalbanner.Admin'));
                    }
                    else{
                        // Adding permissions on the banner images file
                        @chmod($sanitizeImgPath, 0644);
                    }

                    Configuration::updateValue(self::PROMO_BANNER_IMG, $sanitizeImgPath);

                }
                catch (\Exception $e) {
                    $html .= $this->displayError($this->trans($e->getMessage()));
                }

            }

            // User message return
            $html .= $this->displayConfirmation($this->trans('Banner updated.', [], 'Modules.Promotionalbanner.Admin'));
        }


        // --- Form build ---

        $this->context->smarty->assign($this->renderForm());

        return $html . $this->display(__FILE__, 'views/templates/admin/configure.tpl');

    }

    /**
     * Builds the form action URL (AdminModules)
     */
    protected function getFormAction(): string
    {
        return AdminController::$currentIndex
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules');
    }

    /**
     * @return array
     */
    protected function renderForm(): array
    {
        // Get extensions allowed - File "accept" attribute label. Depending on whether GD supports webp
        $imgExtensionsAllowedLabel = !\function_exists('\imagecreatefromwebp')
            ? 'image/jpeg, image/png'
            : 'image/jpeg, image/png, image/webp';

        // Image thumbnail
        $imageThumbnailPath = Configuration::get(self::PROMO_BANNER_IMG)
            ? $this->context->link->protocol_content . Tools::getMediaServer(Configuration::get(self::PROMO_BANNER_IMG)) . $this->_path . 'img/' . Configuration::get(self::PROMO_BANNER_IMG)
            : '';


        return $formData = [
            'legend' => [
                'title' => $this->trans('Promotional Banner Settings', [], 'Modules.Promotionalbanner.Admin'),
                'icon' => 'icon-cogs',
            ],
            'form' => [
                'action' => $this->getFormAction(),
                'token' => Tools::getAdminTokenLite('AdminModules'),
                'input' => [
                    // - Image -
                    [
                        'id'        => self::PROMO_BANNER_IMG,
                        'type'      => 'file',
                        'label'     => $this->trans('Background image', [], 'Modules.Promotionalbanner.Admin'),
                        'name'      => self::PROMO_BANNER_IMG,
                        'desc'      => $this->trans('Choose a background image for your banner. Allowed formats are: %imgBgExtAllowedLabel%. (max. 500 ko, ideal ratio 3:1).', ['%imgBgExtAllowedLabel%' => $imgExtensionsAllowedLabel], 'Modules.Promotionalbanner.Admin'), // SEO : Max 150 ko ideal
                        'value'     => Configuration::get(self::PROMO_BANNER_IMG) ?? '',
                        'extensions_allowed'    => $imgExtensionsAllowedLabel,
                        'button_label'          => $this->trans('Choose a file', [], 'Modules.Promotionalbanner.Admin'),
                        'image_thumbnail_path'  => $imageThumbnailPath,
                        'image_thumbnail_alt'   => $this->trans('Background image of the banner.', [], 'Modules.Promotionalbanner.Admin'),
                    ],
                    // - Title -
                    [
                        'type'      => 'text',
                        'label'     => $this->trans('Title', [], 'Modules.Promotionalbanner.Admin'),
                        'name'      => self::PROMO_BANNER_TITLE,
                        'required'  => true,
                        'desc'      => $this->trans('Set the title of your promotional banner (max 150 char).', [], 'Modules.Promotionalbanner.Admin'),
                        'value'     => Configuration::get(self::PROMO_BANNER_TITLE) ?? '',
                    ],
                    // - Texte -
                    [
                        'type'      => 'textarea',
                        'label'     => $this->trans('Text'),
                        'name'      => self::PROMO_BANNER_TEXT,
                        'desc'      => $this->trans('Define the text/paragraph below your title (max 150 char).', [], 'Modules.Promotionalbanner.Admin'),
                        'value'     => Configuration::get(self::PROMO_BANNER_TEXT) ?? '',
                    ],
                ],
                'submit' => [
                    'icon' => 'save',
                    'title' => $this->trans('Save', [], 'Modules.Promotionalbanner.Admin'),
                    'name' => 'submitPromotionalBanner',
                ],
            ],

        ];

    }


    // -----------------
    // ----- HOOKS -----
    // -----------------

    /**
     * Register Hooks
     * @return bool
     */
    public function registerHooks(): bool
    {
        foreach (self::REGISTER_HOOKS as $hookId) {
            try {
                $this->registerHook($hookId);
            }
            catch (Exception $e) {
                PrestaShopLogger::addLog(
                    "Can't register " . $hookId . ' hook.',
                    2,
                    (int) $e->getCode()
                );
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $params
     * @return false|string
     */
    public function hookDisplayHome(array $params): false|string
    {
        $this->context->smarty->assign([
            'pb_display_module' => Configuration::get(self::PROMO_BANNER_TITLE) !== null, // Display banner if Title not null
            'pb_title' => Configuration::get(self::PROMO_BANNER_TITLE),
            'pb_text'  => Configuration::get(self::PROMO_BANNER_TEXT),
            'pb_img'   => Configuration::get(self::PROMO_BANNER_IMG)
                ? $this->_path.'img/'.Configuration::get(self::PROMO_BANNER_IMG)
                : '',
        ]);

        // We make the template Smarty : /views/templates/hook/banner.tpl
        return $this->display(__FILE__, 'views/templates/hook/banner.tpl');
    }

    public function hookActionAdminControllerSetMedia(array $params): void
    {
        if (Tools::getValue('configure') !== $this->name) {
            return;
        }

        $this->context->controller->addJs($this->_path.'views/js/admin/configure.js');
        // $this->context->controller->addCSS($this->_path.'views/css/admin.css');

        // Passing variables to JS
        Media::addJsDef([
            //'promoBannerFieldImg'   => self::PROMO_BANNER_IMG, // Data passed in the js file ${promoBannerFieldImg}
        ]);
    }


    // -------------------
    // ----- METHODS -----
    // -------------------

    /**
     * Delete current file from disk
     * @return void
     */
    private function deleteCurrentImage(): void
    {
        $current = Configuration::get(self::PROMO_BANNER_IMG);
        if ($current && file_exists(_PS_MODULE_DIR_.$this->name.'/img/'.$current)) {
            @unlink(_PS_MODULE_DIR_.$this->name.'/img/'.$current);
        }
    }

    /**
     * Deletes every image file (jpg, jpeg, png, webp) found in this module’s /img directory.
     * @return void
     */
    private function deleteAllImages(): void
    {
        $dir = _PS_MODULE_DIR_.$this->name.'/img/';
        $fileList  = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);

        foreach ($fileList as $file) {
            if ($file->isFile() && preg_match('/\.(jpe?g|png|webp)$/i', $file->getFilename())) {
                @unlink($file->getPathname());
            }
        }

    }

}