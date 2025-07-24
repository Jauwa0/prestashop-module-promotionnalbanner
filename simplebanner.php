<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Simplebanner extends Module
{

    public function __construct()
    {
        $this->name    = 'simplebanner';         // identifiant technique
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

        $this->displayName = $this->l('Simple banner'); // TODO "l" ?
        $this->description = $this->l("Affiche une bannière de promotion sur la landing page. Configuration : Titre, test, image et durée d'affichage."); // TODO "l" ?
    }

    public function install()
    {
        return parent::install()
            // Valeurs par défaut - Table ps_configuration
            && Configuration::updateValue('SBANNER_TITLE', $this->l('Welcome !'))
            && Configuration::updateValue('SBANNER_TEXT',  $this->l('Summer sale : –20 % on everything.'))
            // Rattaché au Hook "displayHome"
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        // Clean table ps_configuration
        Configuration::deleteByName('SBANNER_TITLE');
        Configuration::deleteByName('SBANNER_TEXT');

        return parent::uninstall();
    }


    // -------------------
    // ----- EDITION -----
    // -------------------

    /**
     * Méthode pour l'édition du module par l'utilisateur.
     * @return string
     */
    public function getContent(): string
    {
        $html = '';

        // Soumission formulaire
        if (Tools::isSubmit('submitSimpleBanner')) {

            // - Récupération des champs -
            $title = Tools::getValue('SBANNER_TITLE');
            $text  = Tools::getValue('SBANNER_TEXT');

            // TODO Sanitize

            // - Enregistrement des valeurs en base -
            Configuration::updateValue('SBANNER_TITLE', $title);
            Configuration::updateValue('SBANNER_TEXT',  $text);

            // - User message return -
            $html .= $this->displayConfirmation($this->l('Settings updated'));
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
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0]['form'] = [
            'legend' => ['title' => $this->l('Banner content')],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Title'),
                    'name'     => 'SBANNER_TITLE',
                    'required' => true,
                    'size'     => 60,
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l('Text'),
                    'name'  => 'SBANNER_TEXT',
                    'rows'  => 4,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-primary pull-right',
            ],
        ];

        $helper = new HelperForm();
        $helper->module               = $this;
        $helper->identifier           = $this->identifier;
        $helper->token                = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex         = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language= $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->title                = $this->displayName;
        $helper->submit_action        = 'submitSimpleBanner';  // name du bouton
        $helper->fields_value = [                              // valeurs pré-remplies
            'SBANNER_TITLE' => Configuration::get('SBANNER_TITLE'),
            'SBANNER_TEXT'  => Configuration::get('SBANNER_TEXT'),
        ];

        // Déclaration du formulaire sous forme d’un tableau et laisse HelperForm générer le HTML Bootstrap.
        return $helper->generateForm($fields_form);
    }


    // -----------------
    // ----- HOOKS -----
    // -----------------

    /** Le code exécuté par PrestaShop quand il construit <div id="content"> */
    public function hookDisplayHome(array $params)
    {
        /*// Données « en dur » pour commencer ; on paramétrera plus tard.
        $this->context->smarty->assign([
            'sb_title' => 'Bienvenue sur notre boutique !',
            'sb_text'  => 'Profitez de -20 % sur tous les produits cet été.',
        ]);*/
        $this->context->smarty->assign([
            'sb_title' => Configuration::get('SBANNER_TITLE'),
            'sb_text'  => Configuration::get('SBANNER_TEXT'),
        ]);

        // On rend le template Smarty : /views/templates/hook/banner.tpl
        return $this->display(__FILE__, 'views/templates/hook/banner.tpl');
    }

}