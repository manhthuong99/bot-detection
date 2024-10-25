define([
    'jquery',
    'jquery/validate'
], function ($) {
    "use strict";

    return function (validator) {
        validator.addRule('validate-special-character', function (value, element) {
            // validate value not have special character { } () [] <>!
            var regex = new RegExp(/^[^{}()[\]<>!]*$/);
            if (!regex.test(value)) {
                return false;
            }

            return true;
        }, $.mage.__("Please enter value without special character"));

        return validator;
    };
});
