<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class DailySummaryOrder extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'dailySummaryOrder';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'MJ-InnovaTech';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Recapitulatif journalier des commandes');
        $this->description = $this->l('Permet l\'envoi journalier d\'un mail récapitulant les commandes faites dans la journée et celles qui sont en cours de traitement.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('DAILYSUMMARYORDER_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('DAILYSUMMARYORDER_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitDailySummaryOrderModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDailySummaryOrderModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'DAILYSUMMARYORDER_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'DAILYSUMMARYORDER_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'DAILYSUMMARYORDER_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'DAILYSUMMARYORDER_LIVE_MODE' => Configuration::get('DAILYSUMMARYORDER_LIVE_MODE', true),
            'DAILYSUMMARYORDER_ACCOUNT_EMAIL' => Configuration::get('DAILYSUMMARYORDER_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'DAILYSUMMARYORDER_ACCOUNT_PASSWORD' => Configuration::get('DAILYSUMMARYORDER_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * Envoi d'un mail contenant le PDF précédement généré en pièce-jointe (légères modifications à apporter une fois la méthode de génération du PDF)
     */
    public function sendMail()
    {
        $to = 'admin@marc-rl.zd.fr';
        $from = 'crontask@marc-rl.zd.fr';
        $subject = 'Récapitulatif des commandes du ' . date('d/m/Y');
        $message_html = '<b>Vous trouverez en pièce-jointe le récapitulatif des commandes du <u>' . date('d/m/Y') . "</u></b>";
        $file = file_get_contents("Doc-site-ecommerce-stage-04_02_21.pdf");

        $boundary_structure = md5(rand());
        $boundary_alternatives = md5(rand());

        $headers = [
                "From: $from",
                "Reply-To: $from",
                "Content-Type: multipart/mixed; boundary=\"$boundary_structure\""
        ];

        /* Encodage du contenu du fichier à envoyer. En accord avec la RFC 2045. */
        $attachment = chunk_split(base64_encode($file));

        /* Le mieux serait d'inclure un fichier de template mais pour l'exemple un ob_start fera l'affaire. */
        ob_start();
        ?>
        --<?=$boundary_structure . PHP_EOL /* Première partie de la structure: le message */?>
        Content-Type: multipart/alternative; boundary="<?=$boundary_alternatives?>"

        --<?=$boundary_alternatives . PHP_EOL /* Seconde alternative: HTML */?>
        Content-Type: text/html; charset="utf-8"
        Content-Transfer-Encoding: 8bit

        <?=$message_html?>

        --<?=$boundary_alternatives /* Fin des alternatives */?>--

        --<?=$boundary_structure . PHP_EOL /* Seconde partie de la structure: le fichier */?>
        Content-Type: application/pdf; name="<?php echo'Recap_du_'.date('d/m/Y')?>"
        Content-Transfer-Encoding: base64
        Content-Disposition: attachment

        <?=$attachment?>

        --<?=$boundary_structure /* Ferme la structure */?>--
        <?php
        $message = ob_get_clean();

        $is_mail_sent = mail($to, $subject, $message, implode(PHP_EOL, $headers));
    }
}
