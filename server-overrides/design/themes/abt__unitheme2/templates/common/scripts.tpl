{script src="js/lib/jquery/jquery-{"main"|fn_abt__ut2_get_jquery_version}.min.js" no-defer=true}


{if $config.current_host =='maxmobiles.ru'}


{* Maxmobiles 2026-10-16: Diginetica отключена совсем (решение пользователя «вообще отключай диджинетику с витрины
   Максмобаилс») – поиск свой, js/addons/my_changes/mm_search.js. Прежний блок (пиксель + client.js 10127) –
   /root/gm-backups/2026-10-16-mm-search-golive/before/design/themes/abt__unitheme2/templates/common/scripts.tpl *}



<style>

.all-promotions__button{
 display:none;   
}
.cp-blog-scroller__image{
      border-radius: 10px;
          border: 0px solid #d9d9d9;
}
.ty-sidebox-important{
     border-radius: 10px;
}
.ab__mcd_descs-section {
    border-radius: 10px;
}
.cp-blog-sidebox__border {
    border-radius: 10px;
}
.cp-blog-grid__image .ty-pict {
    border-radius: 10px;
}
.cp-blog-grid__image {
    border-radius: 10px;
}
#text_links_8323 .ty-text-links__item {
        background-color: #f6f6f6;
    padding: 5px;
    border-radius: 5px;
    padding-left: 10px;
    padding-right: 10px;
    margin-bottom: 5px;
}

  .thanx-page{
        
    background-color: #fff;

    }

.seomenumm .ty-text-links__item{
        background-color: #f6f6f6;
    padding: 5px;
    border-radius: 5px;
    padding-left: 10px;
    padding-right: 10px;
}
.seomenumm .ty-text-links__item:hover{
    background-color:   #eae6e1;
}

.hidecounter{
    display: none;
}

.fill--mm{
        background: #000000;
}

#sw_dropdown_6078{
        background-color: black;
}

#sw_dropdown_6078:hover{
        background-color:#ffcc00;
}


#sw_dropdown_72:hover {
    background-color:#8A9597;
    
    }


 @media (min-width: 770px) {
.ut2-fmw .ut2-lfl:hover{
     background-color: #f5f7fa;
    }
    
    #sw_dropdown_72{
        background-color: black;
        color: white;
}
#sw_dropdown_72 .ut2-icon-outline-menu{
 color:    white !important;
}
 }
    .ut2-lfl > i:before, .ut2-lsl > i:before{
    padding-right: 15px;
}
.ut2-fmw .ut2-lfl{
     padding-left: 5px;
     border-radius:5px;
}
.ut2-lfl > p > a > span:not(.m-label):hover{
      font-weight: bold !important;
}



    .ut2-lfl > p > a > span:not(.m-label) {
        font-weight: 500 !important;
    }
    .ut2-lsl > p a {
        font-weight: 500 !important;
        }
        .ut2-subcategories .ut2-item span {
             font-weight: 500 !important;
        }
        .ty-subcategories__item span {
             font-weight: 500 !important; 
        }

</style>



<script>
$(".showmodalform").on("click", function() {
 $("#dialog1111").dialog(
   {
  width: 700

}
     );
});
</script>


<style>


    .ut2-pb__sticky_add_to_cart-price{
width: 30% !important;      
    float: left;
    }
    .ut2-pb__sticky_add_to_cart .ty-btn__add-to-cart{
          float: right;  
    }
</style>




{/if}



{if $config.current_host =='goodmi.ru'}

{* GOODMi 2026-10-16: Diginetica отключена совсем (решение пользователя «да, переключаем») – поиск свой,
   js/addons/gm_delivery_date/gm_search.js. Прежний блок (пиксель + client.js 10125) –
   /root/gm-backups/2026-10-16-gm-search-golive/before/design/themes/abt__unitheme2/templates/common/scripts.tpl *}



<script>

function getsost(){
    
      $('#sosttraid').prop("disabled", false);
      
     
}


function getprice(){
      var brand = $('#brandtraid').val();
      var model = $('#modeltraid').val();
      var ram = $('#typetraid').val();
      
      var sost = $('#sosttraid').val();
      
      
        $.ajax({
        url: '/index.php?dispatch=testpage.getprice',
        method: 'post',
        
        data: 'brand=' + brand+'&model='+model+'&ram='+ram+'&sost='+sost,
        success: function(data){
          //  alert(data);modeltraid
       $('#resulttraid').empty().append(data);
        }
    });
    
}

function getram(){
      var brand = $('#brandtraid').val();
      var model = $('#modeltraid').val();
      
      
        $.ajax({
        url: '/index.php?dispatch=testpage.getram',
        method: 'post',
        
        data: 'brand=' + brand+'&model='+model,
        success: function(data){
          //  alert(data);modeltraid
       $('#typetraid').empty().append(data);
        }
    });
}

function getmodel(){
  
  var brand = $('#brandtraid').val();
  
    
     $.ajax({
        url: '/index.php?dispatch=testpage.getmodel',
        method: 'post',
        
        data: 'brand=' + brand,
        success: function(data){
          //  alert(data);modeltraid
       $('#modeltraid').empty().append(data);
        }
    });
  
  
    
}

if ($('#brandtraid').length) {
  
  
       $.ajax({
        url: '/index.php?dispatch=testpage.getbrand',
        method: 'post',
       
       
        success: function(data){
           $('#brandtraid').empty().append(data);
         
        }
    });
  
  
  
}





$('#formsearch').submit(function() {
  return false;
});
</script>


<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5MKX8BBL"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->




<style>
.ty-product-review-new-product-review-additional__write-anonymously{
 display:none;   
}

html {
  scroll-behavior: smooth;
}
.ty-wysiwyg-content h2{
    font-weight: 400 !important;
}
.digi-product__actions .digi-product__button{
     border-radius: 10px !important;
}
.all-promotions__button{
    display:none;
    }
    .cp-blog-sidebox__border{
         border-radius: 10px;
    }
    
    .cp-blog-grid__more .ty-btn__primary{ 
        border-radius: 10px;
    }
    .ab__mcd_descs-section{
          border-radius: 10px;
    }
    .cp-blog-grid__image .ty-pict{
        border-radius: 10px;
    }
    .cp-blog-grid__image{
        border-radius: 10px;
    }

.ty-text-links_show_inline{
    padding-left: 7px;
}
.ty-sidebox-important{
    border-radius: 15px;
}
.ab-smc-description{
    border-radius: 15px;
}
.ab-smc-description .ab-smc-more{
    padding-left: 25px;
}

.ty-warehouses-stores-list{
    padding-bottom: 40px;
}
#content_description{
     padding-bottom: 40px !important;
}
#content_features{
        padding-bottom: 40px !important;
}


    .ut2-pb__sticky_add_to_cart-price{
width: 30% !important;      
    float: left;
    }
    .ut2-pb__sticky_add_to_cart .ty-btn__add-to-cart{
          float: right;  
    }
    </style>
{/if}

{if $config.current_host =='goodmi.ru'}









<script> 
    $(function() {
        $(document).ready(function() {
            $('#ab__qobp_product').click(function() {
               // alert('Кнопка была нажата');
               _tmr.push({ type: 'reachGoal', id: 3312714, goal: 'wishadd'});
            });
            
             $('.ty-btn__add-to-cart').click(function() {
             //   alert('Кнопка была нажата');
              _tmr.push({ type: 'reachGoal', id: 3312714, goal: 'adscart'});
            });
        });
        
    })
    

function addToCartSearch(product_id){
    console.log(product_id);
    $('#searchaddform').attr('name','product_form_'+product_id);
    $('#product_name_search').attr('name','product_data['+product_id+'][product_id]');
    $('#product_name_search').val(product_id);
    $('#button_cart_1111111111111').attr('name','dispatch[checkout.add..'+product_id+']');
   // $("#searchaddform").submit();
   var url      = window.location.href;
   $('#redirect_url').val(url);
   
    $("#button_cart_1111111111111").click();
}

function addToCompare(product_id){
    $('#addcomp').attr('href','https://goodmi.ru/index.php?dispatch=product_features.add_product&product_id='+product_id);
       $("#addcomp").click();
    
}
function addToWishlistSearch(product_id){

     console.log(product_id);
         $.ajax({
        url: '/index.php?dispatch=testpage.wishlist_add',
        method: 'post',
        dataType: 'json',
        data: 'product_id=' + product_id,
        success: function(data){
          //  alert(data);
           return data;
        }
    });
  
}
function removeFromWishlistSearch(product_id){

     console.log(product_id);
         $.ajax({
        url: '/index.php?dispatch=testpage.wishlist_del',
        method: 'post',
        dataType: 'json',
        data: 'product_id=' + product_id,
        success: function(data){
           // alert(data);
            return data;
        }
    });
  
}
function checkInCart(product_id){
    
         $.ajax({
        url: '/index.php?dispatch=testpage.checkincart',
        method: 'post',
        dataType: 'json',
        data: 'product_id=' + product_id,
        success: function(data){
           // alert(data);
            console.log(data);
             return data;
        }
    });
}

function checkInCompareList(product_id){
    
         $.ajax({
        url: '/index.php?dispatch=testpage.incomparelist',
        method: 'post',
        dataType: 'json',
        data: 'product_id=' + product_id,
        success: function(data){
           // alert(data);
            console.log(data);
            return data;
        }
    });
}
function checkInWishList(product_id){

    
         $.ajax({
        url: '/index.php?dispatch=testpage.inwishlist',
        method: 'post',
        dataType: 'json',
        data: 'product_id=' + product_id,
        success: function(data){
           // alert(data);
            console.log(data);
             return data;
        }
    });
  
}

</script>

<a class="cm-ajax" id="addcomp" style="display:none;" data-ca-target-id="comparison_list,account_info*,abt__ut2_compared_products" rel="nofollow" href="">
   Добавить в сравнение      </a>

<form style="display:none;" action="https://goodmi.ru/" 
name="product_form_12874" method="post"
id="searchaddformwishlist" class="cm-disable-empty-files cm-ajax cm-ajax-full-render cm-ajax-status-middle cm-processed-form">
    <input type="hidden"  name="result_ids" value="cart_status*,wish_list*,checkout*,account_info*,abt__ut2_wishlist_count">
<input type="hidden" id="redirect_url_wish" name="redirect_url" value="/vakansii1111/">
    <input type="hidden" id="product_name_search_wishlist" name="product_data[12874][product_id]" value="12874">
    <input type="hidden" name="appearance[show_add_to_cart]" value="1">
    <input type="hidden" name="appearance[show_list_buttons]" value="1">
    <input type="hidden" name="appearance[but_role]" value="big">
    <input type="hidden" name="appearance[quick_view]" value="1">
    <input type="hidden" name="is_ajax" value="1">
     <input type="hidden" name="full_render" value="Y">
    
<button id="button_cart_1111111122" class="ty-btn__primary ty-btn__add-to-cart cm-form-dialog-closer  ty-btn" type="submit" name="dispatch[wishlist.add..12874]"><span><i class="ut2-icon-use_icon_cart"></i><bdi>В корзину</bdi></span></button>
    
</form>


<form style="display:none;" action="https://goodmi.ru/" 
name="product_form_12874" method="post"
id="searchaddform" class="cm-disable-empty-files cm-ajax cm-ajax-full-render cm-ajax-status-middle cm-processed-form">
    <input type="hidden"  name="result_ids" value="cart_status*,wish_list*,checkout*,account_info*,abt__ut2_wishlist_count">
<input type="hidden" id="redirect_url" name="redirect_url" value="/vakansii1111/">
    <input type="hidden" id="product_name_search" name="product_data[12874][product_id]" value="12874">
    <input type="hidden" name="appearance[show_add_to_cart]" value="1">
    <input type="hidden" name="appearance[show_list_buttons]" value="">
    <input type="hidden" name="appearance[but_role]" value="action">
    <input type="hidden" name="appearance[quick_view]" value="">
    <button id="button_cart_1111111111111" class="ty-btn__primary ty-btn__add-to-cart cm-form-dialog-closer  ty-btn" type="submit" name="dispatch[checkout.add..12874]"><span><i class="ut2-icon-use_icon_cart"></i><bdi>В корзину</bdi></span></button>
</form>



{script src="js/tygh/bonus.js?v=1.01"}



<style>
.butt{
       background: linear-gradient(0deg,#ff6900,#ff8129);
    border: 0px;
    height: 40px;
    width: 102px;
    border-radius: 7px;
}
    .ut2-subcategories .ut2-item span{
        font-weight:300;
    }
    
    .ut2-filters > div .ty-product-filters__switch .ty-product-filters__title{
          font-weight:300;
    }
    .touchevents .ut2-lfl p > strong, .touchevents .ut2-slw p > strong{
        font-weight:300;
    }
   .top-sticky-panel__filters .ty-dropdown-box__title a, .top-sticky-panel__filters .ty-dropdown-box__title.open a {

           font-weight:300;
    }
</style>


<style>.ty-qty-out-of-stock{
        color: #FF6900 !important;
}
</style>


 
 <style>
 
 @media (max-width: 450px) {
     .brk-block__body__links{
             justify-content: center;
         }
         
     .brk-block__body{
         text-align:center;
     }
     .brk-block__header{
         gap: 0px !important;
             margin: 0 auto;
     }
     
     }
 
 
 .brk-container {
    box-shadow: inset 0 0 2px #f9f9f9 !important;
    background-color: #fefefe;
    border: 1px solid #f4f4f4;
    /*padding: 80px;*/
    position: relative;
}
    .faq-group:not(:last-child) {
    padding-bottom: 5rem;
}
.brk-container p, .brk-container div, .brk-container li {
    font-size: 14px !important;
}
.faq-group {
    padding-bottom: 5rem;
}
.text-info {
    color: #45aaf2 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 1.5rem;
    height: 4rem;
    background-color: #fff;
    border-color: #fff;
    border: 0px;
}


.faq-group .faq-items>.card:not(:last-child), .faq-group .faq-items>.product-bundle:not(:last-child) {
    margin-bottom: 1.5rem;
}
.faq-group .faq-items>.card, .faq-group .faq-items>.product-bundle {
    border-radius: 0.5rem;
}
.brk-container p, .brk-container div, .brk-container li {
    font-size: 14px !important;
}
.card, .card-border-style, .flex-control-thumbs img, .flex-viewport, .product-bundle {
    border: 1px solid #f4f4f4;
    box-shadow: inset 0 0 2px #f9f9f9;
}
.card, .product-bundle {
    background-color: #fefefe;
}


.faq-group .faq-items>.card .card-header, .faq-group .faq-items>.product-bundle .card-header {
    background-color: #f9f9f9;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}
.card-header:first-child {
    border-radius: 0;
}
.brk-container p, .brk-container div, .brk-container li {
    font-size: 14px !important;
}
.font-weight-bold {
    font-weight: 500 !important;
}
.card-header {
    padding: 0.75rem 1.25rem;
    margin-bottom: 0;
    background-color: rgba(0,0,0,0.03);
    border-bottom: 1px solid rgba(0,0,0,0.125);
}
.font-weight-bold {
    font-weight: 500 !important;
}
.clickable {
    cursor: pointer;
}
.card-header {
    background-color: #fff;
    border-bottom: solid 1px #f3f3f3;
}
[data-toggle=collapse] {
    position: relative;
    width: 100%;
    justify-content: left;
    padding-left: 0;
    text-decoration: none;
    color: #333;
    font-weight: 500;
}
.font-weight-bold {
    font-weight: 500 !important;
}

.brk-container p, .brk-container div, .brk-container li {
    font-size: 14px !important;
}


@media (min-width: 1224px)
.row:before, .row:after {
    display: table;
    content: "";
    line-height: 0;
    width: 100%;
}
.row:before, .row:after {
    display: table;
    content: "";
    line-height: 0;
    width: 100%;
}
.brk-container p, .brk-container div, .brk-container li {
    font-size: 14px !important;
}
.pr-4, .px-4 {
    padding-right: 2.5rem !important;
}
.pl-4, .px-4 {
    padding-left: 2.7rem !important;
}
.row:after {
    clear: both;
}
@media (min-width: 1224px)
.row:before, .row:after {
    display: table;
    content: "";
    line-height: 0;
    width: 100%;
}
.row:after {
    clear: both;
}

.row:before, .row:after {
    display: table;
    content: "";
    line-height: 0;
    width: 100%;
}

[data-toggle=collapse]:after {
    content: '';
    opacity: .7;
    position: absolute;
    right: 1rem;
    top: 0;
    width: 10px;
    height: 100%;
    background-image: url(https://goodmi.ru/wp-content/themes/mi92-storefront-child/assets/images/caret-xs.png);
    background-image: -webkit-image-set(url(https://goodmi.ru/wp-content/themes/mi92-storefront-child/assets/images/caret-xs.webp) 1x);
    background-repeat: no-repeat;
    background-position: center right;
}

.card-body {
    flex: 1 1 auto;
    min-height: 1px;
    padding: 1.25rem;
}
div.ui-dialog .buttons-container, div.ui-dialog .buttons-container.picker{
    position: unset !important;
}

</style>
 
 <script>
     
$(function () {
    $('.collapse').hide(300);
    $(document).on('click','.card-header',function (e) {
    	e.preventDefault()
		$(this).parents('.card').toggleClass("active").find('.collapse').slideToggle();
	})
})

function showmap(mapid){
    $('#map0').hide();
    $('#map1').hide();
    $('#map2').hide();
    $('#map3').hide();
    $('#map4').hide();
    $('#map5').hide();
    $('#map'+mapid).show();
    //console.log( $(".mapskroll").offset().top);
    $([document.documentElement, document.body]).animate({
        scrollTop: $(".mapskroll").offset().top-100
    }, 1000);
}

 $(document).ready(function () {
            $('.text-info').click(function () {
                var scrollTop = $('.ty-form-builder').offset().top;
                $(document).scrollTop(scrollTop);
            });
        });
 </script>

{/if}



{scripts}
    {script src="js/lib/jqueryui/jquery-ui.custom.min.js" no-defer=true}

    {script src="js/lib/modernizr/modernizr.custom.js"}
    {script src="js/tygh/core.js"}
    {script src="js/tygh/ajax.js"}
    {script src="js/tygh/history.js"}
    {script src="js/lib/autonumeric/autoNumeric.js"}
    {script src="js/lib/appear/jquery.appear-1.1.1.js"}

{if !$runtime.customization_mode.live_editor}
    {script src="js/lib/tools/tooltip.min.js"}
{/if}

{script src="js/tygh/editors/`$settings.Appearance.default_wysiwyg_editor`.editor.js"}

{script src="js/tygh/responsive.js"}

{if $runtime.customization_mode.live_editor}
    {script src="js/lib/autosize/jquery.autosize.js"}
    {script src="js/tygh/live_editor_mode.js"}
{/if}

<script>
(function(_, $) {

    _.tr({
        abt__ut2__sku_copy_status_success: '{__("abt__ut2.sku_copy_status_success")|escape:"javascript"}',
        abt__ut2__sku_copy_status_fail: '{__("abt__ut2.sku_copy_status_fail")|escape:"javascript"}',
        sku: '{__("sku")|escape:"javascript"}',
        copied: '{__("copied")|escape:"javascript"}',
        cannot_buy: '{__("cannot_buy")|escape:"javascript"}',
        no_products_selected: '{__("no_products_selected")|escape:"javascript"}',
        error_no_items_selected: '{__("error_no_items_selected")|escape:"javascript"}',
        delete_confirmation: '{__("delete_confirmation")|escape:"javascript"}',
        text_out_of_stock: '{__("text_out_of_stock")|escape:"javascript"}',
        items: '{__("items")|escape:"javascript"}',
        text_required_group_product: '{__("text_required_group_product")|escape:"javascript"}',
        save: '{__("save")|escape:"javascript"}',
        close: '{__("close")|escape:"javascript"}',
        notice: '{__("notice")|escape:"javascript"}',
        warning: '{__("warning")|escape:"javascript"}',
        error: '{__("error")|escape:"javascript"}',
        empty: '{__("empty")|escape:"javascript"}',
        text_are_you_sure_to_proceed: '{__("text_are_you_sure_to_proceed")|escape:"javascript"}',
        text_invalid_url: '{__("text_invalid_url")|escape:"javascript"}',
        error_validator_email: '{__("error_validator_email")|escape:"javascript"}',
        error_validator_phone: '{__("error_validator_phone")|escape:"javascript"}',
        error_validator_phone_mask: '{__("error_validator_phone_mask")|escape:"javascript"}',
        error_validator_phone_mask_with_phone: '{__("error_validator_phone_mask_with_phone")|escape:"javascript"}',
        error_validator_phone_phone_number_with_country_selection: '{__("error_validator_phone_phone_number_with_country_selection")|escape:"javascript"}',
        error_validator_integer: '{__("error_validator_integer")|escape:"javascript"}',
        error_validator_multiple: '{__("error_validator_multiple")|escape:"javascript"}',
        error_validator_password: '{__("error_validator_password")|escape:"javascript"}',
        error_validator_required: '{__("error_validator_required")|escape:"javascript"}',
        error_validator_zipcode: '{__("error_validator_zipcode")|escape:"javascript"}',
        error_validator_message: '{__("error_validator_message")|escape:"javascript"}',
        text_page_loading: '{__("text_page_loading")|escape:"javascript"}',
        error_ajax: '{__("error_ajax")|escape:"javascript"}',
        text_changes_not_saved: '{__("text_changes_not_saved")|escape:"javascript"}',
        text_data_changed: '{__("text_data_changed")|escape:"javascript"}',
        placing_order: '{__("placing_order")|escape:"javascript"}',
        order_was_not_placed: '{__("order_was_not_placed")|escape:"javascript"}',
        file_browser: '{__("file_browser")|escape:"javascript"}',
        browse: '{__("browse")|escape:"javascript"}',
        more: '{__("more")|escape:"javascript"}',
        text_no_products_found: '{__("text_no_products_found")|escape:"javascript"}',
        cookie_is_disabled: '{__("cookie_is_disabled")|escape:"javascript"}',
        insert_image: '{__("insert_image")|escape:"javascript"}',
        image_url: '{__("image_url")|escape:"javascript"}',
        loading: '{__("loading")|escape:"javascript"}',
        product_in_wishlist:'{__("product_in_wishlist")|escape:"javascript"}',
        'abt__ut2.add_to_wishlist.tooltip':'{__("abt__ut2.add_to_wishlist.tooltip")|escape:"javascript"}',
        product_added_to_cl:'{__("product_added_to_cl")|escape:"javascript"}',
        add_to_comparison_list:'{__("add_to_comparison_list")|escape:"javascript"}',
        text_editing_raw: '{__("text_editing", ['skip_live_editor' => true])|escape:"javascript"}',
        save_raw: '{__("save", ['skip_live_editor' => true])|escape:"javascript"}',
        cancel_raw: '{__("cancel", ['skip_live_editor' => true])|escape:"javascript"}',
        abt__ut2_of: '{__("of")}',
        'abt__ut2.link_copied': '{__("abt__ut2.link_copied")}',
        'abt__ut2.product_reviews.all_reviews': '{__("abt__ut2.product_reviews.all_reviews")}',
        'abt__ut2.product_reviews.customer_review': '{__("abt__ut2.product_reviews.customer_review")}',
        'abt__ut2.product_reviews.photo_n_of': '{__("abt__ut2.product_reviews.photo_n_of")}'
    });

    $.extend(_, {
        index_script: '{$config.customer_index|escape:javascript nofilter}',
        changes_warning: /*'{$settings.Appearance.changes_warning|escape:javascript nofilter}'*/'N',
        currencies: {
            'primary': {
                'decimals_separator': '{$currencies.$primary_currency.decimals_separator|escape:javascript nofilter}',
                'thousands_separator': '{$currencies.$primary_currency.thousands_separator|escape:javascript nofilter}',
                'decimals': '{$currencies.$primary_currency.decimals|escape:javascript nofilter}'
            },
            'secondary': {
                'decimals_separator': '{$currencies.$secondary_currency.decimals_separator|escape:javascript nofilter}',
                'thousands_separator': '{$currencies.$secondary_currency.thousands_separator|escape:javascript nofilter}',
                'decimals': '{$currencies.$secondary_currency.decimals|escape:javascript nofilter}',
                'coefficient': '{$currencies.$secondary_currency.coefficient}'
            }
        },
        default_editor: '{$settings.Appearance.default_wysiwyg_editor}',
        default_previewer: '{$settings.Appearance.default_image_previewer}',
        current_path: '{$config.current_path|escape:javascript nofilter}',
        current_location: '{$config.current_location|escape:javascript nofilter}',
        images_dir: '{$images_dir}',
        notice_displaying_time: {if $settings.Appearance.notice_displaying_time}{$settings.Appearance.notice_displaying_time}{else}0{/if},
        cart_language: '{$smarty.const.CART_LANGUAGE}',
        language_direction: '{$language_direction}',
        default_language: '{$smarty.const.DEFAULT_LANGUAGE}',
        default_country: '{$settings.Checkout.default_country|escape:javascript}',
        cart_prices_w_taxes: {if ($settings.Appearance.cart_prices_w_taxes == 'Y')}true{else}false{/if},
        regexp: [],
        current_url: '{$config.current_url|fn_url|escape:javascript nofilter}',
        current_host: '{$config.current_host|escape:javascript nofilter}',
        init_context: '{$smarty.request.init_context|escape:javascript nofilter}',
        phone_validation_mode: '{$settings.Appearance.phone_validation_mode}',
        hash_of_available_countries: '{$hash_of_available_countries}',
        hash_of_phone_masks: '{$hash_of_phone_masks}',
        deferred_scripts: []
    });

    {if $live_editor_objects}
        $.extend(_, {
            live_editor_mode: true,
            live_editor_objects: {$live_editor_objects|json_encode nofilter}
        });
    {/if}

    {if !$smarty.request.init_context}

        $(document).ready(function(){
            turnOffPhoneSafariAutofill();

            $.runCart('C');
        });

        $.ceEvent('on', 'ce.commoninit', function () {
            turnOffPhoneSafariAutofill();
        });

        function turnOffPhoneSafariAutofill () {
            if ($.browser.safari) {
                const searchFakeSpan = '<span data-ca-validator="ignore" style="position: absolute; overflow: hidden; width: 1px; height: 1px;">search</span>';

                $('.autofill-off').each(function() {
                    const $field = $(this);
                    if ($field.data('caAutofillIsSet')) {
                        return;
                    }
                    $('[for="' + $field.attr('id') + '"').append(searchFakeSpan);
                    $field.data('caAutofillIsSet', true)
                })

                $('[x-autocompletetype="tel"]').removeAttr('x-autocompletetype');
                var $maskPhoneLabels =  $('.cm-mask-phone-label:not(.autofill-on)');
                if (!$maskPhoneLabels.length || $('[data-ca-validator="ignore"]', $maskPhoneLabels).length) {
                    return;
                }
                $maskPhoneLabels.append(searchFakeSpan);
            }
        }

    {/if}

    {if $config.tweaks.anti_csrf}
        _.security_hash = '{""|fn_generate_security_hash}';
    {/if}
}(Tygh, Tygh.$));
</script>

{script src="js/lib/maskedinput/jquery.maskedinput.min.js"}

{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{script src="js/lib/jquery-bind-first/jquery.bind-first-0.2.3.js"}
{script src="js/lib/inputmask-multi/jquery.inputmask-multi.js"}
{if $settings.Appearance.phone_validation_mode === "phone_number_with_country_selection"}
    {script src="js/lib/libphonenumber-js/libphonenumber-max.js"}
{/if}
{script src="js/lib/owlcarousel/owl.carousel.min.js"}

{script src="js/tygh/phone_mask.js"}


{hook name="index:scripts"}
{/hook}

{/scripts}