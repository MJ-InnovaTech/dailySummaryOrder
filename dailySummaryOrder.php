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

    public function generatePDF()
    {

        $date = date('d-m-Y');
        $output = '';

        require_once('TCPDF/tcpdf.php');  
        $obj_pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);  
        $obj_pdf->SetCreator(PDF_CREATOR);  
        $obj_pdf->SetTitle("Récapitulatif des commandes du ". $date);  
        $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);  
        $obj_pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));  
        $obj_pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
        $obj_pdf->SetDefaultMonospacedFont('helvetica');  
        $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);  
        $obj_pdf->SetMargins(PDF_MARGIN_LEFT, '5', PDF_MARGIN_RIGHT);  
        $obj_pdf->setPrintHeader(false);  
        $obj_pdf->setPrintFooter(false);  
        $obj_pdf->SetAutoPageBreak(TRUE, 10);  
        $obj_pdf->SetFont('helvetica', '', 8);  
        $obj_pdf->AddPage();  
        $content = ''; 
        $content .= '  
        <meta charset="UTF-8">
        <style type="text/css">
            th {
                text-align: center;
                font-weight: bold;
                background-color: #d4e2ff;
            }
            .prix {
                text-align: right;
            }
        </style>
        <h3 align="center">Récapitulatif des commandes du '. $date .'</h3><br /><br />  
        <table border="1" cellspacing="0" cellpadding="5">  
            <tr>  
                    <th width="12%">Reference</th>  
                    <th width="15%">Prenom</th>  
                    <th width="15%">Nom</th>  
                    <th width="25%">Email</th>  
                    <th width="10%">Prix</th>  
                    <th width="20%">Etat Commande</th>  
            </tr>  
        ';  
        $result = doSQLRequest();
        while($row = mysqli_fetch_array($result))  
        {    
            $content .='<tr>  
                            <td>'. utf8_encode($row["reference"]).'</td>  
                            <td>'. utf8_encode($row["firstname"]).'</td>  
                            <td>'. utf8_encode($row["lastname"]).'</td>  
                            <td>'. utf8_encode($row["email"]).'</td>  
                            <td class="prix">'.number_format($row["total_products"], 2, ',', ' ').' €</td>
                            <td>'. utf8_encode($row["name"]).'</td>   
                        </tr>  
                            ';  
        }  
        $content .= '</table>';  
        $obj_pdf->writeHTML($content);  
        $fileName = 'Recap_Commande_Du_'.$date.'.pdf';
        $obj_pdf->Output(__DIR__ ."/".$fileName, 'F');
        sendMail($fileName);
    
    }

    function sendMail($fileName)
    {
        $destinataire='admin@leonidas-le-jardin-des-gourmandises.fr';
        $from='admin@leonidas-le-jardin-des-gourmandises.fr';
        mb_internal_encoding('UTF-8');
        $sujet='Récapitulatif des commandes du '. date('d/m/Y');
        $encoded_subject = mb_encode_mimeheader($sujet, 'UTF-8', 'B', "\r\n", strlen('Subject: '));
        $message='<h3>Vous trouverez en pièce-jointe le récapitulatif des commandes du <u>'. date('d/m/Y') . '</u></h3>';

        $boundary = "_".md5 (uniqid (rand()));

        //on selectionne le fichier à partir d'un chemin relatif 
            $attached_file = file_get_contents('/public_html/modules/dailySummaryOrder/'.$fileName); //file name ie: ./image.jpg
            $attached_file = chunk_split(base64_encode($attached_file));
        //on recupere ici le nom du fichier
            $pos=strrpos($fileName,"/");
            if($pos!==false)$file_name=substr($fileName,$pos+1);
            else $file_name=$fileName;

        //on recupere ici le type du fichier
            $pos=strrpos($fileName,".");
            if($pos!==false)$file_type="/".substr($fileName,$pos+1);
            else $file_type="";

            //echo "file_type=$file_type";
            $attached = "\n\n". "--" .$boundary . "\nContent-Type: application".$file_type."; name=\"$file_name\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment;                  filename=\"$file_name\"\r\n\n".$attached_file . "--" . $boundary . "--";

        //on formate les headers
            $headers ="From: ".$from." \r\n";
            $headers .= "MIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        //on formate le corps du message
            $body = "--". $boundary ."\nContent-Type: text/html; charset=utf-8\r\n\n".$message . $attached;

        //on envoie le mail
        mail($destinataire,$encoded_subject,$body,$headers);
    }

    function doSQLRequest()
    {
        $connect = new mysqli("localhost", "msgcbkbs_prestashop", "Leonid@s-Admin1.", "msgcbkbs_92Napz6gP");  

        $sql = 'SELECT ps_orders.reference, ps_customer.firstname, ps_customer.lastname, ps_customer.email, ps_orders.total_products, ps_order_state_lang.name
                    FROM ps_orders, ps_customer, ps_order_state_lang
                    WHERE ps_orders.current_state IN (1, 2, 3, 13, 14)
                    AND ps_orders.id_customer = ps_customer.id_customer
                    AND ps_order_state_lang.id_lang = 2
                    AND ps_order_state_lang.id_order_state = ps_orders.current_state;';
                    
        $result = mysqli_query($connect, $sql);
        return $result;
    }
}
