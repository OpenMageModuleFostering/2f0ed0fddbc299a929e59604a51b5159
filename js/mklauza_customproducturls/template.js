/**
 * Marcin Klauza - Magento developer
 * http://www.marcinklauza.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to marcinklauza@gmail.com so we can send you a copy immediately.
 *
 * @category    Mklauza
 * @package     Mklauza_CustomProductUrls
 * @author      Marcin Klauza <marcinklauza@gmail.com>
 * @copyright   Copyright (c) 2015 (Marcin Klauza)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

jQuery.noConflict();

(function($) {
    $.fn.closestToOffset = function(offset) {
        var el = null, elOffset, x = offset.left, y = offset.top, distance, dx, dy, minDistance;
        this.each(function() {
            elOffset = $(this).offset();

            if (
            (x >= elOffset.left)  && (x <= elOffset.right) &&
            (y >= elOffset.top)   && (y <= elOffset.bottom)
            ) {
                el = $(this);
                return false;
            }

            var offsets = [[elOffset.left, elOffset.top], [elOffset.right, elOffset.top], [elOffset.left, elOffset.bottom], [elOffset.right, elOffset.bottom]];
            for (off in offsets) {
                dx = offsets[off][0] - x;
                dy = offsets[off][1] - y;
                distance = Math.sqrt((dx*dx) + (dy*dy));
                if (minDistance === undefined || distance < minDistance) {
                    minDistance = distance;
                    el = $(this);
                }
            }
        });
        return el;
    };
    
    $.fn.textWidth = function(){
      var html_org = jQuery(this).val();
      var html_calc = '<span id="temp-span">' + html_org + '</span>';
      jQuery('body').append(html_calc);
      var width = jQuery('span#temp-span').width();
      jQuery('span#temp-span').remove();
      return width + (jQuery(this).val().length)*1.33;
    };

    $.fn.adjustSize = function() {
        return jQuery(this).each(function(){
            jQuery(this).css('width', jQuery(this).textWidth()+5 + 'px' );
        });
    };

    $.fn.validatePattern = function() {
        jQuery(this).children().each(function(){
            if(jQuery(this).hasClass('inputTags-field') && jQuery(this).next().hasClass('inputTags-field')) {
                if( jQuery(this).next().val() !== '') {
                    jQuery(this).val( jQuery(this).val() + '-' + jQuery(this).next().val()).adjustSize();
                }
                jQuery(this).next().remove();
            }
        });
    };    
})(jQuery);   

serializePattern = function() {
    var patternParam = '';
    jQuery('.js-pattern').children().each(function(){
        if(jQuery(this).hasClass('inputTags-item')) {
            patternParam += '{'+jQuery(this).data('value')+'}';
        } else if(jQuery(this).hasClass('inputTags-field')) {
            patternParam += jQuery(this).val();
        }
    });
    return patternParam;
};

enableFileds = function() {
    jQuery('.js-tag-cloud').sortable('enable');
    jQuery('.js-pattern').sortable('enable');
    console.log('enable');
};    
disableFields = function() {
    jQuery('.js-tag-cloud').sortable('disable');
    jQuery('.js-pattern').sortable('disable');
    console.log('disable');
};        

jQuery(document).on('keydown', '.inputTags-list input.inputTags-field', function(){
    jQuery(this).adjustSize();
});  


//jQuery(document).on('change', '.js-pattern', function(){
//    submitChanges();
//});
//
//jQuery(document).on('change', '#mklauza_customproducturls_general_pattern_inherit', function() {
//    this.checked ? disableFields() : enableFileds();
//});

jQuery(document).on('click', '.js-pattern', function(e){  
    var clickedX = e.clientX;
    var element = jQuery(this).children().closestToOffset({left: clickedX, top: e.clientY});
    if(!element.length) {
        return false;
    }
    var elPositionX = element[0].offsetLeft;
    if(clickedX < elPositionX) {
        if(!jQuery(element).hasClass('inputTags-field') && !jQuery(element).prev().hasClass('inputTags-field')) {
            jQuery('<input type=text value="" class="inputTags-field ui-sortable-handle"/>').insertBefore(element).adjustSize().focus();
        } else {
            if(jQuery(element).hasClass('inputTags-field')) {
                jQuery(element).focus();
            } else {
                jQuery(element).prev().focus();
            }
        }
    } else {
        if(!jQuery(element).hasClass('inputTags-field') && !jQuery(element).next().hasClass('inputTags-field')) {
            jQuery('<input type=text value="" class="inputTags-field ui-sortable-handle"/>').insertAfter(element).adjustSize().focus();
        } else {
            if(jQuery(element).hasClass('inputTags-field')) {
                jQuery(element).focus();
            } else {
                jQuery(element).next().focus();
            }
        }   

    }
});





