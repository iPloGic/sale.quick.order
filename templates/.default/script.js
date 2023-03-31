(function() {
    'use strict';

    if (!!window.JSSaleQuickOrderComponent)
        return;

    window.JSSaleQuickOrderComponent = function(params) {
        this.componentPath = params.componentPath || '';
        this.parameters = params.parameters || '';
        this.fields = params.fields || '';

        let obj = this;
        let formData = [];
        let valid;

        this.fields.push({code: "PRODUCT_ID", required: "N", type: "STRING"});
        if(this.parameters.ADD_COMMENT === "Y") {
            this.fields.push({code: "USER_COMMENT", required: this.parameters.COMMENT_REQUIRED, type: "STRING"});
        }
        if(this.parameters.USE_USER_CONSENT !== "NO") {
            this.fields.push({code: "USER_CONSENT", required: "Y", type: "Y/N"});
        }

        BX.bindDelegate(
            document.body,
            'click',
            { class: "sqo-form_button" },
            function() {
                obj.sendRequest('save');
            }
        );

        BX.bindDelegate(
            document.body,
            'click',
            { class: "sqo-close" },
            function() {
                obj.closeForm();
            }
        );
    };

    window.JSSaleQuickOrderComponent.prototype.sendRequest = function(action) {

        let obj = this;

        this.processFormData();

        if (this.valid !== true) {
            return false;
        }

        let data = {
            action: action,
            parameters: this.parameters,
            form_data: this.formData,
        };

        BX.ajax.loadJSON(
            this.componentPath + '/ajax.php',
            data,
            function(res) {
                let contaner = BX("sqo-popup");
                let result_success = contaner.querySelector('.sqo-result_success');
                let result_text = contaner.querySelector('.sqo-result_text');
                let result_fail = contaner.querySelector('.sqo-result_fail');
                let result_error_text = contaner.querySelector('.sqo-result_error_text');

                result_success.style.display = "none";
                result_text.style.display = "none";
                result_fail.style.display = "none";
                BX.cleanNode(result_error_text);
                result_error_text.style.display = "none";

                if (res.errors !== undefined) {
                    result_fail.style.display = "block";
                    let errors = [];
                    res.errors.forEach(function(entry) {
                        let error = BX.create(
                            'li',
                            {'text' : entry.message}
                        );
                        errors.push(error);
                    });
                    let error_list = BX.create(
                        'ul',
                        {'children' : errors}
                    );
                    BX.adjust(result_error_text, {html: error_list.outerHTML});
                    result_error_text.style.display = "block";
                }
                if (res.success === "Y") {
                    result_success.style.display = "block";
                    result_text.style.display = "block";
                }
        });
    };

    window.JSSaleQuickOrderComponent.prototype.processFormData = function() {

        let obj = this;
        let fData = [];
        let form = BX("sqo-form");
        let value;
        let valid = true;

        obj.fields.forEach(function(entry) {
            let error = false;
            let fdf = BX("sqo_id_" + entry.code);
            let front = fdf;
            front.classList.remove("has_error");
            value = fdf.value;
            if(entry.type === "Y/N") {
                if(fdf.checked) {
                    value = "Y";
                }
                else {
                    value = "N";
                }
            }
            fData[entry.code] = value;
            if (entry.required === "Y") {
                error = obj.validateField(entry, value);
            }
            if (error) {
                valid = false;
                front.classList.add("has_error");
            }
        });

        this.formData = fData;
        this.valid = valid;
    };

    window.JSSaleQuickOrderComponent.prototype.validateField = function(entry, value) {
        if (entry.type === "Y/N") {
            if(value !== "Y") {
                return true;
            }
        }
        else {
            if (value === "") {
                return true;
            }
            else {
                let good = true;
                let re = false;
                if (entry.is_phone === "Y") {
                    re = /^[\d\+][\d\(\)\ -]{4,17}\d$/;
                }
                if (entry.is_email === "Y") {
                    re = /^[\w]{1}[\w-\.]*@[\w-]+\.[a-z]{2,4}$/i;
                }
                if (re !== false) {
                    good = re.test(value);
                    if(good !== true) {
                        return true;
                    }
                }
            }
        }
        return false;
    };

    window.JSSaleQuickOrderComponent.prototype.closeForm = function() {
        BX('sqo-underlayer').style.display = 'none';
        BX('sqo-popup').style.display = 'none';
    };

})();