<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Promotionalbanner extends Module
{
    private const MAX_IMG_BG_SIZE = 500000; // 500 ko
    private const MAX_IMG_BG_PX = 4096; // Limite la taille max d'un côté
    private const IMG_BG_EXT_ALLOWED_WITH_WEBP = ['jpg', 'jpeg', 'png', 'webp'];
    private const IMG_BG_EXT_ALLOWED_NO_WEBP = ['jpg', 'jpeg', 'png'];


    public function __construct()
    {
        $this->name    = 'promotionalbanner';         // identifiant technique
        $this->tab     = 'advertising_marketing';   // rubrique du catalogue modules
        $this->version = '1.0.0';
        $this->author  = 'Aurelien DAVID';
        $this->need_instance = 0;                  // pas de charge inutile en BO
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;                   // gabarits basés sur Bootstrap

        parent::__construct();

        $this->displayName = $this->l('Bannière promotionnelle'); // TODO "l" ? TODO Faire l'internationalisation
        $this->description = $this->l("Affiche une bannière de promotion sur la page d'accueil du site. Configuration : Titre, test, image et durée d'affichage."); // TODO "l" ?
    }

    public function install()
    {
        // TODO Si vide => Pas afficher
        return parent::install()
            // Valeurs par défaut - Table ps_configuration
            && Configuration::updateValue('SBANNER_TITLE', $this->l('Welcome !'))
            && Configuration::updateValue('SBANNER_TEXT',  $this->l('Summer sale : –20 % on everything.'))
            && Configuration::updateValue('SBANNER_IMG',   '')
            // Rattaché au Hook "displayHome"
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        // Clean table ps_configuration
        Configuration::deleteByName('SBANNER_TITLE');
        Configuration::deleteByName('SBANNER_TEXT');
        Configuration::deleteByName('SBANNER_IMG');

        // Deletes the disk image if it exists
        $this->deleteCurrentImage();

        return parent::uninstall();
    }


    // -------------------
    // ----- EDITION -----
    // -------------------

    /**
     * Méthode pour l'édition du module par l'utilisateur.
     * @return string
     * @throws Exception
     */
    public function getContent(): string
    {
        $html = '';

        // Soumission formulaire
        if (Tools::isSubmit('submitPromotionalBanner')) {

            // --- Enregistrement des valeurs en base ---

            // - Title - Sanitize + updateValue -

            $title = trim(Tools::getValue('SBANNER_TITLE'));
            // Raille raisonnable (255 car. max) – UTF-8 safe // TODO Définir une taille raisonnable
            if (!Validate::isLength($title, 0, 255)) {
                $this->errors[] = $this->module->l('Title is too long (255 chars max).'); // TODO
            }
            // Disallow any tags or control characters
            if (!Validate::isGenericName($title)) {
                return $this->displayError($this->l('Title contains invalid characters.')); // TODO
            }
            // Remove tags
            $title = strip_tags($title);

            Configuration::updateValue('SBANNER_TITLE', $title);


            // - Text - Sanitize + updateValue -

            $text  = Tools::getValue('SBANNER_TEXT');
            Configuration::updateValue('SBANNER_TEXT',  $text);


            // - Image -

            if (isset($_FILES['SBANNER_IMG']) && !empty($_FILES['SBANNER_IMG']['tmp_name'])) {

                // Get extensions allowed - Depending on whether GD supports webp
                $imgBgExtAllowed = !\function_exists('\imagecreatefromwebp') ? self::IMG_BG_EXT_ALLOWED_NO_WEBP : self::IMG_BG_EXT_ALLOWED_WITH_WEBP;

                // - Contrôle PrestaShop de base -
                if ($error = \ImageManager::validateUpload($_FILES['SBANNER_IMG'], self::MAX_IMG_BG_SIZE, $imgBgExtAllowed)) { // /!\ Check the maximum size according to the need
                    $html .= $this->displayError($this->l($error));
                    //return $this->displayError($error); // PS error message management
                    // TODO Comment quitter cette partie ?
                }

                // - Scan antivirus -
                /*if (!$this->clamAvOk($_FILES['SBANNER_IMG']['tmp_name'])) {
                    return $this->displayError("Le fichier est potentiellement infecté par un virus."); // ClamAV
                }*/

                // - Name of the new image -
                $extension = Tools::substr(strrchr($_FILES['SBANNER_IMG']['name'], '.'), 1);
                $fileName = 'banner_'.md5(time()).'.'.$extension;
                $destDir = _PS_MODULE_DIR_.$this->name.'/img/';

                // - Avoid overly large images and "image bombs" -
                try{
                    $this->processWithGd($_FILES['SBANNER_IMG']['tmp_name'], $destDir.$fileName);
                }
                catch (Exception $e){
                    $html .= $this->displayError($this->l($error));
                }


                // Delete old image
                $this->deleteCurrentImage();


                // - Copy file in destination directory -
                if (!copy($_FILES['SBANNER_IMG']['tmp_name'], $destDir.$fileName)) { // Just a copy because PS still uses it internally after
                    return $this->displayError($this->l('Copy failed.'));
                }

                Configuration::updateValue('SBANNER_IMG', $fileName);

                // TODO Du coup de pas suppression du fichier temporaire ?
                // TODO Pourquoi là pas de "@chmod($destPath, 0644);" ?
            }

            // User message return
            $html .= $this->displayConfirmation($this->l('Bannière mise à jour.')); // TODO Internationnal
        }

        return $html.$this->renderForm();
    }

    /**
     * Formulaire d'édition des valeurs d'affichage
     * HelperForm doc : https://devdocs.prestashop-project.org/9/development/components/helpers/helperform/
     * @return string
     */
    protected function renderForm(): string
    {
        // Get extensions allowed - Depending on whether GD supports webp
        $imgBgExtAllowedLabel = !\function_exists('\imagecreatefromwebp') ? "jpg, jpeg, png" : "webp, jpg, jpeg, png" ;

        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        $form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Paramètres de la bannière promotionnelle'),
                'icon' => 'icon-cogs',
            ],
            'input'  => [
                // - Image -
                [
                    'type'  => 'file',
                    'label' => $this->l('Image de fond'),
                    'name'  => 'SBANNER_IMG',
                    'display_image' => true,
                    'desc'  => $this->l("Choisissez une image de fond pour votre bannière. Les formats autorisés sont : " . $imgBgExtAllowedLabel . " (max. 500 ko, ratio idéal 3:1)."), // SEO : Max 150 ko idéal
                    // Thumbnail if already uploaded
                    'image' => Configuration::get('SBANNER_IMG')
                        ? '<img src="'.$this->_path.'img/'.Configuration::get('SBANNER_IMG').'" 
                               alt="" class="img-thumbnail" style="width:auto; max-width:100%; max-height:150px; aspect-ratio: initial;" />'
                        : '',
                ],
                // - Title -
                [
                    'type'      => 'text',
                    'label'     => $this->l('Title'),
                    'name'      => 'SBANNER_TITLE',
                    'required'  => true,
                    'size'      => 60,
                    'desc'      => $this->l("Définissez le titre de votre bannière promotionnelle."),
                ],
                // - Texte -
                [
                    'type'      => 'textarea',
                    'label'     => $this->l('Text'),
                    'name'      => 'SBANNER_TEXT',
                    'rows'      => 4,
                    'desc'      => $this->l("Définissez le texte/paragraphe en dessous de votre titre."),
                ],
            ],
            'submit' => [
                'icon' => 'save',
                'title' => $this->l('Sauvegarder'),
                'class' => 'btn btn-primary pull-right',
            ],
        ];

        $helper->module               = $this;
        $helper->identifier           = $this->identifier;
        $helper->token                = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex         = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language= $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->title                = $this->displayName;
        $helper->submit_action        = 'submitPromotionalBanner';  // name du bouton
        $helper->fields_value = [                              // valeurs pré-remplies
            'SBANNER_TITLE' => Configuration::get('SBANNER_TITLE'),
            'SBANNER_TEXT'  => Configuration::get('SBANNER_TEXT'),
        ];

        // Déclaration du formulaire sous forme d’un tableau et laisse HelperForm générer le HTML Bootstrap.
        return $helper->generateForm($form);
    }


    // -----------------
    // ----- HOOKS -----
    // -----------------

    /** Le code exécuté par PrestaShop quand il construit <div id="content"> */
    public function hookDisplayHome(array $params)
    {
        $this->context->smarty->assign([
            'sb_title' => Configuration::get('SBANNER_TITLE'),
            'sb_text'  => Configuration::get('SBANNER_TEXT'),
            'sb_img'   => Configuration::get('SBANNER_IMG')
                ? $this->_path.'img/'.Configuration::get('SBANNER_IMG')
                : '',
        ]);

        // We make the template Smarty : /views/templates/hook/banner.tpl
        return $this->display(__FILE__, 'views/templates/hook/banner.tpl');
    }


    // -------------------
    // ----- METHODS -----
    // -------------------

    private function imageRecordingProcess()
    {

    }

    /**
     * Delete current file from disk
     * @return void
     */
    private function deleteCurrentImage()
    {
        $current = Configuration::get('SBANNER_IMG');
        if ($current && file_exists(_PS_MODULE_DIR_.$this->name.'/img/'.$current)) {
            @unlink(_PS_MODULE_DIR_.$this->name.'/img/'.$current);
        }
    }

    /** Vérification du fichier avec ClamAV (clamscan) */
    private function clamAvOk(string $path): bool
    {
        $cmd = 'clamscan --no-summary --stdout '.escapeshellarg($path);
        exec($cmd, $out, $code);   // 0=OK, 1=infecté, 2=erreur
        return $code === 0;
    }

    /**
     * Avoid overly large images and "image bombs".
     * Processing via GD (JPEG/PNG) - Included by default in PHP
     * @param string $src
     * @param string $dest
     * @return void
     * @throws Exception
     */
    private function processWithGd(string $src, string $dest): void
    {
        $info = getimagesize($src);
        if ($info === false) {
            throw new \Exception('Fichier image invalide.');
        }
        [$w, $h, $type] = $info;

        // - Checking the dimensions -
        if ($w > self::MAX_IMG_BG_PX || $h > self::MAX_IMG_BG_PX) {
            // Delta ratio to be at the limit size
            $ratio = min(self::MAX_IMG_BG_PX / $w, self::MAX_IMG_BG_PX / $h);
            $nw    = (int) round($w * $ratio);
            $nh    = (int) round($h * $ratio);
        }
        else {
            $nw = $w;
            $nh = $h;
        }

        // - Decoding / Recoding -
        switch ($type) {

            // WEBP
            case IMAGETYPE_WEBP:
                if (!\function_exists('\imagecreatefromwebp')) { // Depending on whether GD supports webp
                    throw new \Exception('GD n’a pas été compilé avec le support WEBP.');
                }
                $srcIm  = imagecreatefromwebp($src);
                $destIm = imagecreatetruecolor($nw, $nh);
                imagealphablending($destIm, false);
                imagesavealpha($destIm, true);
                imagecopyresampled($destIm, $srcIm, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagewebp($destIm, $dest, 100);
                break;

            // PNG
            case IMAGETYPE_PNG:
                $srcIm = imagecreatefrompng($src);
                $destIm = imagecreatetruecolor($nw, $nh);
                imagealphablending($destIm, false);
                imagesavealpha($destIm, true);
                imagecopyresampled($destIm, $srcIm, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagepng($destIm, $dest, 0); // 0 = sans compression, 9 = max
                break;

            // DEFAULT - JPEG
            default:
                $srcIm  = imagecreatefromjpeg($src);
                $destIm = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($destIm, $srcIm, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagejpeg($destIm, $dest, 100);

        }

        // Memory cleaning
        imagedestroy($srcIm);
        imagedestroy($destIm);
    }

}