<?php
require_once(_PS_MODULE_DIR_ . "rj_makitosync/rj_makitosync.php");
include_once(_PS_MODULE_DIR_ . "rj_makitosync/classes/RjMakitoItemPrint.php");
class Product extends ProductCore
{

    public static $pricePrint;

    public static function priceCalculation(
        $id_shop,
        $id_product,
        $id_product_attribute,
        $id_country,
        $id_state,
        $zipcode,
        $id_currency,
        $id_group,
        $quantity,
        $use_tax,
        $decimals,
        $only_reduc,
        $use_reduc,
        $with_ecotax,
        &$specific_price,
        $use_group_reduction,
        $id_customer = 0,
        $use_customer_price = true,
        $id_cart = 0,
        $real_quantity = 0,
        $id_customization = 0
    ) {
        $price = parent::priceCalculation(
            $id_shop,
            $id_product,
            $id_product_attribute,
            $id_country,
            $id_state,
            $zipcode,
            $id_currency,
            $id_group,
            $quantity,
            $use_tax,
            $decimals,
            $only_reduc,
            $use_reduc,
            $with_ecotax,
            $specific_price,
            $use_group_reduction,
            $id_customer,
            $use_customer_price,
            $id_cart,
            $real_quantity,
            $id_customization
        );
        // return $price;
        if (!Module::isEnabled('rj_makitosync')) {
            return $price;
        }

        $_GET;
        $_POST;
        
        $price = Product::incrementPriceRoanja($price);
        $pricePrint = Product::calculaPricePrintMakito(); 
        static $address = null;
        static $context = null;

        if ($context == null) {
            $context = Context::getContext()->cloneContext();
        }

        if ($address === null) {
            if (is_object($context->cart) && $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')} != null) {
                $id_address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
                $address = new Address($id_address);
            } else {
                $address = new Address();
            }
        }

        $address->id_country = $id_country;
        $address->id_state = $id_state;
        $address->postcode = $zipcode;

        $tax_manager = TaxManagerFactory::getManager($address, Product::getIdTaxRulesGroupByIdProduct((int) $id_product, $context));
        $product_tax_calculator = $tax_manager->getTaxCalculator();

        if ($use_tax) {
            $pricePrint = $product_tax_calculator->addTaxes($pricePrint);
        }
        
        return $price + $pricePrint;
    }

    public static function incrementPriceRoanja($price)
    {
         $price_increment = Configuration::get('RJ_PRICE_INCREMENT', true);
         if (Configuration::get('RJ_PRICE_ALCANCE', true)) {
             if (Configuration::get('RJ_PRICE_INCREMENT_TYPE', true)) {
                 $price += $price * $price_increment / 100;
             } else {
                 $price += $price_increment;
             }
         }
         return $price;
    }

    public static function calculaPricePrintMakito()
    {
        $pricePrint = 0;
        $dataprint = rj_makitosync::getValuesPrintJobs();
        if($dataprint){
            foreach ($dataprint as $datacode) {
                $pricePrint += RjMakitoItemPrint::calculaPrecioPrint($datacode);
            }
        }

        if(Tools::getValue('controller') === "cart"){
            self::$pricePrint = $pricePrint;
        }

        return $pricePrint;
    }

}