var field_map = new Map();
jQuery(document).ready(function(e) {
     
    var fields
    var requiredFields = new Map();
    var fielddetails = new Map();
    mo_sf_sync_fill_users();
    let wpfields = jQuery("#mo-sf-sync-wpfields").html();
    var wp_object_select = jQuery("#wp_object_select").val()
    mo_sf_sync_get_wp_fields(wp_object_select)
    jQuery('select').selectWoo({})

    setTimeout(() => {
        jQuery('.mo-sf-sync-alert').fadeOut('slow')
    }, 10000);

    if (jQuery("#object_select").attr("data-object")) {
        mo_sf_sync_get_fields(jQuery("#object_select").attr("data-object"))
    }

    jQuery('#wp_object_select').on('change',function (){
        let wp_object=jQuery(this).val()
        mo_sf_sync_get_wp_fields(wp_object)
        jQuery('.mo_sf_sync_show_wp_obj').val(wp_object)

        if(wp_object == 'post')
            jQuery('#taxonomy-sync-section').prop('hidden',false)
        
        else
            jQuery('#taxonomy-sync-section').prop('hidden',true) 
    })

    function mo_sf_sync_get_wp_fields(wp_object){
        if(wp_object === undefined)
            return false;
        
        let formdata = new FormData();
        formdata.append('wp_object',wp_object);
        formdata.append('action','mo_sf_sync_ajax_submit');
        ajax_request = jQuery.ajax({
            url: ajax_object_sf.ajax_url_sf,
            type: "POST",
            data: formdata,
            processData: false,
            contentType: false
        });
        ajax_request.done((response)=>{
            if(response.data.error_description){
                let error_msg = response.data.error_description
                if(error_msg === true || error_msg === 'true')
                    error_msg = "Please reload the page once!"
                alert(error_msg)
                return [];
            }
            wpfields= response.data;
               
            jQuery(".wp_field").each(function(){             
                let u=jQuery(this)
                
                u.find('option').remove().end()
                daraattt = mo_sf_sync_create_wp_fields(u.attr("data-field"));
                u.append(daraattt)
            })
        });
    }

    window.mo_sf_sync_create_wp_fields = function(u){
        let options="";
        let saved_object = jQuery("#saved_object").attr("data-saved-object");
        let object_select = jQuery("#wp_object_select").val();
            wpfields.forEach((wpfield)=>{
            options+=`<option value=${wpfield} ${saved_object==object_select && u==wpfield?"selected":""}>`
                options+=`${wpfield}</option>`
            })
        return options;
    }

    function mo_sf_sync_get_fields(object, str=false) {
        jQuery('#savebutton').prop('disabled', true);
        let formdata = new FormData()
        formdata.append('object', object);
        formdata.append('action', 'mo_sf_sync_ajax_submit');
        ajax_request = jQuery.ajax({
            url: ajax_object_sf.ajax_url_sf,
            type: "POST",
            data: formdata,
            processData: false,
            contentType: false,
        beforeSend:function(){
            jQuery('#sf-obj-search').prop('hidden',false)
            jQuery('.field_mapping').prop('hidden',true)
            jQuery('#save_obj_map_btn').prop('hidden',true)
        },
        complete:function(){
            jQuery('#sf-obj-search').prop('hidden',true)
            jQuery('.field_mapping').prop('hidden',false)
            jQuery('#save_obj_map_btn').prop('hidden',false)
        }
        });
        ajax_request.done((response) => {
            if (response.data.error_description) {
                let error_msg = response.data.error_description
                if(error_msg === true || error_msg === 'true')
                    error_msg = "Please reload the page once!"
                alert(error_msg)
                return;
            }
            fields = response.data.fields || [];
            mo_sf_sync_req_fields()

            jQuery(".object_field").each(function() {
                let u = jQuery(this)
                u.find('option').remove().end()
                u.append(mo_sf_sync_create_fields(u.attr("data-field")))
            })
           

            req_fields_present = jQuery("#set_field_map").length
            switch (req_fields_present) {
                case 0:
                    if(str){
                        jQuery(".sf-wp-mapping-div").empty();
                        fields.forEach((field) => {
                            if (field.createable && !field.nillable && !field.defaultedOnCreate && field.type !== "boolean") {
                                mo_sf_sync_populate_req_fields(field);
                            }
                        })
                    }
                    break;
                case 1:
                    jQuery(".sf-wp-mapping-div").empty();
                    fields.forEach((field) => {
                        if (field.createable && !field.nillable && !field.defaultedOnCreate && field.type !== "boolean") {
                            mo_sf_sync_populate_req_fields(field);
                        }
                    })
                    break;
            }
            mo_sf_sync_show_add_attribute_widget()
        });
        ajax_request.fail((error) => {});
    }

    function mo_sf_sync_show_add_attribute_widget(){
        dom = jQuery(".sf-wp-mapping-div").append(
            `
            <div class="new_fields_div ">
            </div>
            <div>
                <section class="accordion">
                    <input type="checkbox" name="collapse" checked="checked">
                    <h2 class="handle">
                        <label class = "mo-sf-sync-coll-div-head" ><b>Add New Salesforce Field</b></label>
                    </h2>
                    <div class="mo-sf-dflex content">
                        <div class=" mo-sf-col-md-8" >
                            <select class="form-control object_field" id="sel_sf_field" style="width:300px">
                                ${mo_sf_sync_create_fields()}
                            </select>
                        </div>
                        <div class="mo-sf-col-md-4">
                            <div id="add_sf_attr">
                                <input type="button" class="form-control mo-sf-btn-cstm" value="Add Salesforce Field" onclick = "mo_sf_sync_add_attr()">
                            </div>
                        </div>
                    </div>            
                </section>
            </div>
            `
        )
        dom.children().last().children().find('select').selectWoo({})
    }

    
    function mo_sf_sync_req_fields() {
        fields.sort((a, b) => {
            if (a.createable && !a.nillable && !a.defaultedOnCreate && a.type !== "boolean")
                return -1;
            if (b.createable && !b.nillable && !b.defaultedOnCreate && b.type !== "boolean")
                return 1;
            return 0;
        })
        requiredFields.clear()
        let i = 0
        fields.forEach((field) => {
            if (field.createable && !field.nillable && !field.defaultedOnCreate && field.type !== "boolean") {
                requiredFields.set(field.name, field.label)
            }
            fielddetails.set(field.name,field.type,field.length)
            if (field.updateable)
                i++
        })
        mo_sf_sync_handle_no_fields(i);
        field_map.clear()
        fields.forEach(function(element) {
            field_map.set(element.name, element)
        });
    }

    function mo_sf_sync_handle_no_fields(i) {

        if (i == 0) {
            jQuery('.object_field').prop('disabled', true);
            jQuery('#mo_sf_sync_add_rows').prop('disabled', true);
            jQuery('.wordpress_field').prop('disabled', true);
            jQuery('.custom_fields').prop('disabled', true);
            jQuery('#mo_sf_sync_add_rows').prop('disabled', true);
            jQuery('#mo_sf_sync_add_rows_custom').prop('disabled', true);
            jQuery('#mo_sf_sync_add_rows_custom').prop('readonly', true);
            jQuery('#savebutton').prop('disabled', true);
            jQuery('.del_row_btn').prop('disabled', true);
            jQuery('.mo-sf-sync-no-fields').css({ 'display': 'inline' });
        } else {
            jQuery('.object_field').prop('disabled', false);
            jQuery('#mo_sf_sync_add_rows').prop('disabled', false);
            jQuery('.wordpress_field').prop('disabled', false);
            jQuery('.custom_fields').prop('disabled', false);
            jQuery('#mo_sf_sync_add_rows').prop('disabled', false);
            jQuery('#mo_sf_sync_add_rows_custom').prop('disabled', false);
            jQuery('#mo_sf_sync_add_rows_custom').prop('readonly', false);
            jQuery('#savebutton').prop('disabled', false);
            jQuery('.del_row_btn').prop('disabled', false);
            jQuery('.mo-sf-sync-no-fields').css({ 'display': 'none' });
        }
    }

    function mo_sf_sync_toggle_save() {
        jQuery(".field-submit").each(function() {
            jQuery(this).prop('disabled', function(i, v) { return !v; })
        });
    }

    window.mo_sf_sync_create_fields = function(u) {
        let options = "<option id='sf_field_options' value=''>Select A New Salesforce field</option>";
        let saved_object = jQuery("#saved_object").attr("data-saved-object")
        let object_select = jQuery("#object_select").val()
        var savedobject = jQuery('#saved_mapping').val()
        var sav_obj = false
        if(savedobject)
            sav_obj = JSON.parse(savedobject)

        fields.forEach((field) => {
            if (field.updateable) {
                if ((field.createable && !field.nillable && !field.defaultedOnCreate && field.type !== "boolean") || (sav_obj != false && jQuery.inArray(field.name,sav_obj) !== -1)) 
                    return
                options += `<option value=${[field.name,field.type,field.length,'updatable']} ${saved_object==object_select && u==field.name?"selected":""}>`
                if (field.createable && !field.nillable && !field.defaultedOnCreate && field.type !== "boolean") {
                    options += `<span style="color:red;">*</span> `
                }
                options += `${field.label} (${field.type})</option>`
            }
            else{
                options += `<option value=${[field.name,field.type,field.length,'non_updatable']} ${saved_object==object_select && u==field.name?"selected":""}>`
                options += `${field.label} (${field.type}) [Non Updatable Field]</option>`
            }
        })
        return options;
    }

    jQuery("#object_select").on('change', function() {
        jQuery('.preloader').fadeIn('slow');
        let object = jQuery(this).val();
        if(object === ''){
            jQuery('.field_mapping').prop('hidden',true)
            return
        }
        mo_sf_sync_get_fields(object,true)
    })

    function mo_sf_sync_add_loader_class(loader) {
        let loader_template = jQuery(".mo-sf-sync-loader-container");
        loader_template.appendTo(loader);
        jQuery('#success_m').addClass('mo-sf-sync-loader').position({
            my: "right center",
            at: "right bottom",
            of: "#targetElement"
          });;
        jQuery('#error_m').addClass('mo-sf-sync-loader');
        jQuery('#support_m').addClass('mo-sf-sync-loader');
        jQuery('#loader').removeClass('mo-sf-sync-loader');
    }
    jQuery.fn.center = function(parent) {
        if(parent) {
          parent = this.parent();
        } else {
          parent = window;
        }
        this.css({
          "position": "absolute",
          "top": (((jQuery(parent).height() - this.outerHeight()) / 2) + jQuery(parent).scrollTop() + "px"),
          "left": (((jQuery(parent).width() - this.outerWidth()) / 2) + jQuery(parent).scrollLeft() + "px")
        });
        return this;
      }
      jQuery("div.myclass:nth-child(1)").center(true);

    function mo_sf_sync_handle_success(result, option_name) {
        jQuery('#loader').addClass('mo-sf-sync-loader');
        if (result.success == true) {
            jQuery("#saved_object").attr("data-saved-object", jQuery("#object_select").val())
            jQuery(".object_field").each(function() {
                jQuery(this).attr("data-field", jQuery(this).val())
            })
            if (option_name === "mo_sf_sync_contact_us_query_option"){
                jQuery('#support_m').addClass('mo-sf-sync-loader')
                var array = jQuery.makeArray(jQuery('#support_m'))
                jQuery('#support_m').removeClass('mo-sf-sync-loader')
            }
            else
                jQuery('#success_m').removeClass('mo-sf-sync-loader').position({
                    my: "right center",
                    at: "right bottom",
                    of: "#targetElement"
                  });
        } else {
            jQuery('#error_m').removeClass('mo-sf-sync-loader');
        }
    }

    function mo_sf_sync_handle_error(error) {
        jQuery('#loader').addClass('mo-sf-sync-loader');
        jQuery('#error_m').removeClass('mo-sf-sync-loader');
    }

    jQuery('.mo_sf_sync_ajax_submit_form').submit(function(e) {
        e.preventDefault();
        let loader = jQuery(this).find(".loader-placeholder");
        let option_name = jQuery(this).find("input[name='option']").val();
        if (option_name === "mo_sf_sync_client_object") {
            let reqFields = new Map(requiredFields)
            let list = [];
            jQuery(".object_field").each(function() {
                let field = jQuery(this).val()
                if (reqFields.has(field)) {
                    reqFields.delete(field)
                } else
                    list.push(jQuery(this).find(":selected").text())
            })
            if (reqFields.size) {
                alert("Required field missing: " + Array.from(reqFields.values()).join(', '))
                return;
            }
            i = 0;
           jQuery(".wordpress_field").each(function(){
               let field = jQuery(this).val()
               if(field === ""){
                   i++;
               }
           })
       if(i){
           alert('Mapping with --none-- ')
           return;
       }
        }
        let nonce_ = jQuery(this).find("input[name='nonce_']").val();
        let tab = jQuery(this).find("input[name='tab']").val();
        let data = jQuery(this).find("input:not([name='option'],[name='tab'],[name='nonce_']), select, textarea").serialize();
        let formdata = new FormData();
        formdata.append('action', 'mo_sf_sync_ajax_submit');
        formdata.append('option', option_name);
        formdata.append('tab', tab);
        formdata.append('nonce_', nonce_);
        formdata.append('data', data);
        ajax_request = jQuery.ajax({
            url: ajax_object_sf.ajax_url_sf,
            type: "POST",
            data: formdata,
            processData: false,
            contentType: false,
            beforeSend: function() {
                mo_sf_sync_add_loader_class(loader);
            }
        });
        ajax_request.done(function(result) { mo_sf_sync_handle_success(result, option_name) });
        ajax_request.fail(mo_sf_sync_handle_error);
    });

    jQuery('#push_attributes').click(function() {
        wpid = jQuery("select[name='upn_id']").val()
        mo_sf_sync_test_connection(wpid)
        return false;
    })

    jQuery('#reports_table').DataTable({
        "order": [[ 6, "desc" ]]
    });

    jQuery('#search_users').click(mo_sf_sync_fill_users)

    function mo_sf_sync_fill_users() {
        let formdata = new FormData()
        formdata.append('query', jQuery("input[name='query']").val());
        formdata.append('action', 'mo_sf_sync_ajax_submit');
        ajax_request = jQuery.ajax({
            url: ajax_object_sf.ajax_url_sf,
            type: "POST",
            data: formdata,
            processData: false,
            contentType: false
        });
        ajax_request.done((response) => {
            if (Array.isArray(response.data)) {
                let options = "";
                response.data.forEach(function(user) {
                    options += `<option value="${user.data.ID}">${user.data.user_login}</option>`
                })
                if (options == "")
                    jQuery("#push_plh").prop("hidden", false);
                else {
                    jQuery("#push_plh").prop("hidden", true);
                    jQuery("#upn_id").find('option').remove().end().append(options)
                }
            }
        });
        ajax_request.fail((error) => {});
    }

    jQuery('#enter_search').on('keypress', function(e) {
        if (e.which == 13) {

            let formdata = new FormData()
            formdata.append('query', jQuery("input[name='query']").val());
            formdata.append('action', 'mo_sf_sync_ajax_submit');
            ajax_request = jQuery.ajax({
                url: ajax_object_sf.ajax_url_sf,
                type: "POST",
                data: formdata,
                processData: false,
                contentType: false
            });
            ajax_request.done((response) => {
                if (Array.isArray(response.data)) {
                    let options = "";
                    response.data.forEach(function(user) {
                        options += `<option value="${user.data.ID}">${user.data.user_login}</option>`
                    })
                    if (options == "")
                        jQuery("#push_plh").prop("hidden", false);
                    else {
                        jQuery("#push_plh").prop("hidden", true);
                        jQuery("#upn_id").find('option').remove().end().append(options)
                    }
                }
            });
            ajax_request.fail((error) => {});
        }
    });

    jQuery("#mo_sf_sync_add_rows").click(function() {   
        
        let dom = jQuery("#mo-sf-sync-custom-attr").append(
            `            
            <tr class="onclick_elements">
                <td class="left-div">
                    <select class="form-control object_field" style="width:300px"  name="${jQuery("#mo-sf-sync-wpfields").first().val()}" >
                        ${mo_sf_sync_create_fields()}
                    </select>
                </td>
                <td class="right-div">
                    <select class="form-control wp_field" style="width:100%" onchange="changeWPField(event)">
                        ${mo_sf_sync_create_wp_fields()}
                    </select>
                </td>
                <td>
                        <button class="button del_row_btn mo-sf-btn-cstm" onclick="deleteAttr(event);return false;">X</button>
                </td>
            </tr>
            `
        )
        dom.children().last().children().find('select').selectWoo({})
    })

    jQuery("#mo_sf_sync_add_rows_custom").click(function() {
        let dom = jQuery("#mo-sf-sync-custom-attr-val").append(
            `            
            <tr>
                <td class="left-div">
                    <select class="form-control object_field mo_sf_sync_custom" onchange="changeField(event); return false;">
                        ${mo_sf_sync_create_fields()}
                    </select>
                </td>
                <td class="right-div">
                    <input type="text"/>
                </td>
                <td>
                        <button class="button del_row_btn mo-sf-btn-cstm" onclick="deleteAttr(event);return false;">X</button>
                </td>
            </tr>
            `
        )
        dom.children().last().children().find('select').selectWoo({
            width: "300px"
        })
    })

    window.changeField = function(event) {
        let label = event.target.value
        let field = field_map.get(label)
        let dom = jQuery(event.target).parent().siblings(".right-div")

        if (field.type === "picklist") {
            options = `<select name="custom_${field.name}" class="custom_fields" style="width:100%;">`
            field.picklistValues.forEach(({ value, label }) => {
                options += `<option value="${value}">${label}</option>`;
            });
            options += "</select>";
            dom = dom.children().remove().end()
                .append(options)
            dom.find('select').selectWoo({
                width: "100%"
            })
        } else if (field.type === 'boolean') {
            options = `
            <select name="custom_${field.name}" class="custom_fields mo-sf-select-width">
                <option>true</option>
                <option>false</option>
            </select>
            `
            dom = dom.children().remove().end()
                .append(options)
        } else {
            dom.children().remove().end()
                .append(`
                <input type="text" name="custom_${field.name}" class="custom_fields" style="width:100%;"/>
            `)
        }
    }

    window.changeWPField = function(event) {
        let dom, name;
        if (event.target.value === "__custom__") {
            dom = jQuery(event.target).parent()
            dom.replaceWith(`
                <input type="text" onchange="changeWPField(event)" style="width:300px"/>
            `)
            return;
        }
        if (jQuery(event.target).is("input")) {
            dom = jQuery(event.target).siblings().children("select")
        } else {
            dom = jQuery(event.target).parent().siblings().children("select")
        }
        name = jQuery(event.target).val()
        dom.attr("name", name)
    }

    window.deleteAttr = function(event,removed_field) {
        sf_wp = jQuery('#sync_sf_to_wp').is(':checked')
        let dom = jQuery(event.target).parent().parent().parent().parent().parent().remove();
        if(typeof removed_field === 'object'){
            del_name = removed_field.id
        }else{
            removed_field = JSON.stringify(removed_field)
            removed_field = removed_field.split(" ");
            del_name = removed_field[1];
        }        
        let Field_removed_data= field_map.get(del_name)
        if(Field_removed_data.updateable === true){
            jQuery('#sel_sf_field').append(    
                `<option value=${Field_removed_data.name}>${Field_removed_data.label} (${Field_removed_data.type})</option>`
            )
        }else{
            jQuery('#sel_sf_field').append(    
                `<option value=${Field_removed_data.name}>${Field_removed_data.label} (${Field_removed_data.type}) [Non Updatable Field]</option>`
            )
        }
    }
    
    config_mode_radio = jQuery('input[name=config_mode]:checked', '#app_config_save_client_configuration').val();
    if (!(config_mode_radio === "undefined"))
        mo_sf_sync_change_config_mode(config_mode_radio);

    jQuery('#app_config_save_client_configuration input').on('change', function() {
        config_mode_radio = jQuery('input[name=config_mode]:checked', '#app_config_save_client_configuration').val();
        mo_sf_sync_change_config_mode(config_mode_radio);
    });

    function mo_sf_sync_change_config_mode(config_mode_radio) {
        switch (config_mode_radio) {
            case 'auto':
                jQuery('#auto-config').prop("hidden", false);
                jQuery('#manual-config').prop("hidden", true);
                break;
            case 'manual':
                jQuery('#manual-config').prop("hidden", false);
                jQuery('#auto-config').prop("hidden", true);
                break;
        }
    }

    selected_radio = jQuery('input[name=env_select]:checked', '#app_config_save_client_configuration').val();
    
    if (!(selected_radio === "undefined"))
        mo_sf_sync_toggle_env_url_field(selected_radio);

    jQuery('#app_config_save_client_configuration input').on('change', function() {
        selected_radio = jQuery('input[name=env_select]:checked', '#app_config_save_client_configuration').val();
        mo_sf_sync_toggle_env_url_field(selected_radio);
    });

    function mo_sf_sync_create_req_fields(requiredField) {

        let options = "<option value=''>--None--</option>";
        let saved_object = jQuery("#saved_object").attr("data-saved-object")
        let object_select = jQuery("#object_select").val()

        fields.forEach((field) => {
            if (field.updateable) {
                if (requiredField == field.name) {
                    options += `<option value=${field.name} selected>`
                } else {
                    options += `<option value=${field.name} >`
                }
                if (field.createable && !field.nillable && !field.defaultedOnCreate && field.type !== "boolean") {

                    options += `<span style="color:red;">*</span> `
                }
                options += `${field.label} (${field.type})</option>`
            }
        })
        return options;
    }

    function mo_sf_sync_populate_req_fields(field) {
        if(field.type == 'picklist'){
            def = 'Standard Fields'
            option = 'Conditional Field'
        }
        else{
            def = 'Standard WordPress Fields'
            option = 'Static'
        }
        dom = jQuery(".sf-wp-mapping-div").append(
            `       
            <section class="accordion">
                <input type="checkbox" name="collapse" checked="checked">
                <input type="hidden" name="sf_fields[]" value="${field.name}">
                <input type="hidden" name="name_constraint[]" value="${field.name}">
                <input type="hidden" name="type_constraint[]" value="${field.type}">
                <input type="hidden" name="maxlength_constraint[]" value="${field.size}">
                <input type="hidden" id="Removed_field" name="Removed_field" value="${field.name}">
                <h2 class="handle">
                    <label class = "mo-sf-sync-coll-div-head" ><b>${field.label}<span class="required-fields-asterisk" style="color:red;font-style: italic;">*</span></b><span style="font-size: 14px !important;font-style: italic;">  [Name: ${field.name}, Type: ${field.type}, Max-length: ${field.size}] </span>
                    <div class="req-field-delete-button" style="float:right ;"></div>
                    </label>
                </h2>
                <div class="mo-sf-dflex content" style="border-bottom: 0;">
                    <div class=" mo-sf-col-md-3" style="border-bottom:none">
                        <span>Field Type</span>
                    </div>
                    <div class="mo-sf-col-md-8-field-mapping">
                        <div>
                            <select class="form-control" style="width: 300px;" id="field_type_${field.name}" name="field_types[]">
                                <option value="standard">${def}</option> 
                                <option value="static">${option}</option> 
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mo-sf-dflex content" style="margin-top:-20px !important">
                    <div class="mo-sf-col-md-3">
                        <span>Select field</b></span>
                    </div>
                    <div class="mo-sf-col-md-8-field-mapping">
                        <div id="sync_sel_fields_div_${field.name}">
                            
                        </div>
                    </div>
                </div>                
		    </section>            
            `
        )
        dom.children().last().children().find('select').selectWoo({})
        selected_type = ''
        jQuery('#field_type_'+field.name).on('change',function(){
            selected_type = jQuery(this).val()
            if(selected_type == 'static'){
                if(field.type === "picklist"){
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                    .append(mo_sf_sync_show_object_mapping_for_picklist(field.name)).find('select').selectWoo({})
                }
                else
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                    .append(`<input type="text" name="wordpress_fields[]" class="custom_fields" style="width:300px;"/>`)
            }
            else{
                if (field.type === "picklist") {
                    options = `<select name="wordpress_fields[]"  style="width: 300px;">`
                    field.picklistValues.forEach(({ value, label }) => {
                        options += `<option value="${value}">${label}</option>`;
                    });
                    options += "</select>";
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
                } else if (field.type === 'boolean') {
                    options = `
                    <select name="wordpress_fields[]" style="width: 300px;">
                        <option>true</option>
                        <option>false</option>
                    </select>
                    `
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
                        .append(options)
                } else {
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                        .append(`
                        <select class="form-control wp_field select2" style="width: 300px;" name="wordpress_fields[]">
                            ${mo_sf_sync_create_wp_fields()}
                        </select>
                    `).find('select').selectWoo({})
                }
            }
        })
        if (field.type === "picklist") {
            options = `<select name="wordpress_fields[]"  style="width: 300px;">`
            field.picklistValues.forEach(({ value, label }) => {
                options += `<option value="${value}">${label}</option>`;
            });
            options += "</select>";
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
        } else if (field.type === 'boolean') {
            options = `
            <select name="wordpress_fields[]" style="width: 300px;">
                <option>true</option>
                <option>false</option>
            </select>
            `
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
                .append(options)
        } else {
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                .append(`
                <select class="form-control wp_field select2" style="width: 300px;" name="wordpress_fields[]">
                    ${mo_sf_sync_create_wp_fields()}
                </select>
            `).find('select').selectWoo({})
        }
    }

    function mo_sf_sync_toggle_env_url_field(env_name) {
        pardot_buid_path = '/lightning/setup/PardotAccountSetup/home' 
        switch (env_name) {
            case 'prod':
                jQuery('input[name=env_link]').prop("readonly", true).hide();
                jQuery('#auth_uri').hide()
                jQuery('input[name=env_link]').prop("value", "https://login.salesforce.com");
                jQuery('.mo-sf-sync-pid-button').attr("href", "https://login.salesforce.com"+pardot_buid_path);
                break;
            case 'test':
                jQuery('input[name=env_link]').prop("readonly", true).hide();
                jQuery('#auth_uri').hide()
                jQuery('input[name=env_link]').prop("value", "https://test.salesforce.com");
                jQuery('.mo-sf-sync-pid-button').attr("href", "https://test.salesforce.com"+pardot_buid_path);
                break;
            case 'custom':
                jQuery('input[name=env_link]').prop("readonly", false).show();
                jQuery('#auth_uri').show()
                let cust_url = jQuery('input[name=env_link]', '#app_config_save_client_configuration').val();
                if (jQuery.inArray(cust_url, ["", "https://login.salesforce.com", "https://test.salesforce.com"]) !== -1) {
                    jQuery('input[name=env_link]').prop("value", "");
                    jQuery('input[name=env_link]').prop("placeholder", "Enter Custom URL (NOTE: URL must end with '.salesforce.com')").prop('required',true)
                }
                break;
        }
    }
    jQuery('#save_config').hide()
    jQuery('#test-config').show()

    if(document.getElementById('mo_sf_sync_app_type')) {
        app_type = document.getElementById('mo_sf_sync_app_type').value
        if(app_type === 'manual'){
            mo_sf_sync_show_manual_configuration()
        }
        else if(app_type === 'preconnected'){
            mo_sf_sync_show_automatic_configuration()
        } 
    }

})

function mo_sf_sync_valid_query(f) {
    !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(
        /[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
}

function redirect_to_field_mapping() {
    window.location.href = window.location + "&tab=manage_users";
}

function redirect_to_pardot_guide(){
    url = window.location.href
    var tempurl = url.split("?")
    var baseurl = tempurl[0]
    window.location.href = baseurl + "?page=pardot_guide"
}

function close_and_redirect_to_advance_sync() {
    window.location.href = window.location + "&tab=advance_sync_options";
}

function mo_sf_sync_show_manual_configuration(){
    jQuery('.automatic_display').show()
    jQuery('.manual_display').hide()
    jQuery('#back_to_automatic').show()
    jQuery('#back_to_manual').hide()
    jQuery('#configuration_body').show()
    jQuery('#auto-config').hide()
    jQuery('#manual-config').show()
    jQuery('#save_config').show()
    jQuery('#test-config').show()
    jQuery("input[name='client_id']").prop('required',true).prop('disabled',false)
    jQuery("input[name='client_secret']").prop('required',true).prop('disabled',false)
    jQuery("input[name='redirect_uri']").prop('required',true).prop('disabled',false)
    jQuery("#automatic-app-connect").hide()
    jQuery("#manual-app-connect").show()
}

function mo_sf_sync_show_automatic_configuration(){
    jQuery('.automatic_display').hide()
    jQuery('.manual_display').show()
    jQuery('#back_to_automatic').hide()
    jQuery('#back_to_manual').show()
    jQuery('#configuration_body').show()
    jQuery('#manual-config').hide()
    jQuery('#auto-config').show()
    jQuery('#save_config').hide()
    jQuery('#test-config').show()
    jQuery("input[name='client_id']").prop('required',false).prop('disabled',true)
    jQuery("input[name='client_secret']").prop('required',false).prop('disabled',true)
    jQuery("input[name='redirect_uri']").prop('required',false).prop('disabled',true)
    jQuery("#automatic-app-connect").show()
    jQuery("#manual-app-connect").hide()
}


function mo_sf_sync_show_config_window() {
    
    function validURL(str) {
        var res = str.match(/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);
        return (res !== null);

    }
    let client_id = jQuery("input[name='client_id']").val();
    let redirect_uri = encodeURI(jQuery("input[name='redirect_uri']").val());
    let sf_url = jQuery("input[name='env_link']").val();
    if(sf_url.endsWith(".salesforce.com") === false )
        return;
    let url = encodeURI(sf_url + `/services/oauth2/authorize?response_type=code&grant_type=authorization_code&scope=api refresh_token&client_id=${client_id}&redirect_uri=${redirect_uri}`);
    if (client_id && redirect_uri && validURL(url))
        var myWindow = window.open(url, "Salesforce Authorization", "scrollbars=1 width=800, height=600");
    else alert(`Invalid client credentials: ${client_id?"":"client id"} ${redirect_uri?"":"redirect uri"}`)
}

function mo_sf_sync_show_config_window_for_pre_connected_app() {
    
    function validURL(str) {
        var res = str.match(/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);
        return (res !== null);

    }
    let is_pardot_enabled = document.getElementById('is_pardot_int_enabled').checked
    let client_id = '3MVG9ZL0ppGP5UrDG2dF37_B5GQiUeXQmGPtH0qf6eemjdTTRb5XHUppenzIsq5dI8EBkXWSGoPFTXKzvkUOR'
    let redirect_uri = 'https://connect.xecurify.com/'
    let sf_url = jQuery("input[name='env_link']").val();
    if(sf_url.endsWith(".salesforce.com") === false && sf_url != null)
        return;
    let customer_url = jQuery("#mo_sf_sync_home_url").val();
    if(is_pardot_enabled === true)
        scopes = 'api refresh_token pardot_api';
    else
        scopes = 'api refresh_token';
    let url = sf_url + `/services/oauth2/authorize?response_type=code&state=`+customer_url+`&grant_type=authorization_code&scope=${scopes}&client_id=${client_id}&redirect_uri=${redirect_uri}`;
    if (client_id && redirect_uri && validURL(url)){
        var myWindow = window.open(url, "Salesforce Authorization", "scrollbars=1 width=800, height=600");
    }
    else alert(`Invalid client credentials: ${client_id?"":"client id"} ${redirect_uri?"":"redirect uri"}`)
}

jQuery.fn.isInViewport = function() {
    var elementTop = jQuery(this).offset().top;
    var elementBottom = elementTop + jQuery(this).outerHeight();

    var viewportTop = jQuery(window).scrollTop();
    var viewportBottom = viewportTop + jQuery(window).height();

    return elementBottom > viewportTop && elementTop < viewportBottom;
};

function remove_previous() {
    jQuery(".req_elements").remove();
    jQuery(".onclick_elements").remove();

}

function enable_authorize(){
    jQuery('#authorize').prop('disabled',false);
}

function enable_test_config(){
    jQuery('#test-config').prop('disabled',false);
}

function test_configuration_status(){
    jQuery('#test-config1').prop('disabled',false);
    jQuery('#preconn_conn_to_sf').val('Reconnect To Salesforce')
}

function mo_sf_sync_change_direction(togg_val){
    if(togg_val== 'sync_wp_to_sf' ){
        dis_toggle = "sync_sf_to_wp";
        jQuery(".req-field-delete-button").empty()
        jQuery(".required-fields-asterisk").append(
            '<span style="color:red;font-style: italic;">*</span>'
        )
    }     
    else{
        dis_toggle = "sync_wp_to_sf";
        let ele = jQuery('#Removed_field').val()
        ele = " ' " + `${ele}` + " ' "
        jQuery(".required-fields-asterisk").empty()
        jQuery(".req-field-delete-button").append(
            `<button type="button" style="border:none;cursor:pointer;background:none">
                <span class="dashicons dashicons-trash" onclick="deleteAttr(event,${ele});return false;"></span>
            </button>`
        )
        
    }
    if(jQuery('#'+togg_val).is(':checked') === true){
        jQuery('#'+dis_toggle).prop('checked',false);
    }
}

function mo_sf_sync_test_connection(wpid){
    home_url = jQuery('#mo_sf_sync_home_url').val()
    url = home_url+`?option=mo_sf_sync_test_connection&mo_sf_sync_wpid=${wpid}`
    window.open(url,'Salesforce Test Connection',"scrollbars=1 width=800, height=600")
}

function mo_sf_sync_copyToClipboard(copyButton, element, copyelement) {
    var temp = jQuery("<input>");
    jQuery("body").append(temp);
    temp.val(jQuery(element).text()).select();
    document.execCommand("copy");
    temp.remove();
    jQuery(copyelement).text("Copied");

    jQuery(copyButton).mouseout(function() {
        jQuery(copyelement).text("Copy to Clipboard");
    });
}

function mo_sf_sync_dismiss_int_trial_notification_bar(){
    let formdata = new FormData();
        
    formdata.append('integration_trial_request','integration_trial_request_box_dissmissed');
    formdata.append('action','mo_sf_sync_ajax_submit');
    ajax_request = jQuery.ajax({
        url: ajax_object_sf.ajax_url_sf,
        type: "POST",
        data: formdata,
        processData: false,
        contentType: false
    });
    
    ajax_request.done((response)=>{
        if(response.data == 'transient_set_successfully')
            jQuery('#int_trial_div_id').remove()
        
    });
}

function mo_sf_sync_dismiss_notification_bar(){
    let formdata = new FormData();
        
    formdata.append('trial_request','trial_request_box_dissmissed');
    formdata.append('action','mo_sf_sync_ajax_submit');
    ajax_request = jQuery.ajax({
        url: ajax_object_sf.ajax_url_sf,
        type: "POST",
        data: formdata,
        processData: false,
        contentType: false
    });
    
    ajax_request.done((response)=>{
        if(response.data == true)
            jQuery('#normal_trial_div_id').remove()
    });
}

function mo_sf_sync_add_attr(){ 
    let newfield = document.querySelector('#sel_sf_field').value;
     if(newfield.indexOf(',') === -1){
            field_name = newfield.split("(")
            field_object = field_map.get(field_name[0].trim())
            if(field_object.updateable === true){
                newfield = field_object.name + "," + field_object.type + "," + field_object.length + ",updatable"
            }else{
                newfield = field_object.name + "," + field_object.type + "," + field_object.length + ",non_updatable"
            }
    }
    if(newfield === " " || newfield === undefined || newfield === ""){
        alert("Please Select a Salesforce field first")
        return;
    }
    var selfield = document.getElementById("sel_sf_field");  
    selfield.remove(selfield.selectedIndex)
    const new_field = newfield.split(",")

        if(new_field[1] == 'picklist'){
            def = 'Standard Fields'
            option = 'Conditional Field'
        }
        else{
            def = 'Standard WordPress Fields'
            option = 'Static'
        }
        removed_field =" ' " + `${new_field[0]}` + " ' "
        let dom = jQuery(".new_fields_div").append(
            `
            <div>
                <section class="accordion">
                    <input type="checkbox" name="collapse" checked="checked">
                    <input type="hidden" name="sf_fields[]" value="${new_field[0]}">
                    <input type="hidden" name="name_constraint[]" value="${new_field[0]}">
                    <input type="hidden" name="type_constraint[]" value="${new_field[1]}">
                    <input type="hidden" name="maxlength_constraint[]" value="${new_field[2]}">
                    <input type="hidden" name="updatable_status[]" value="${new_field[3]}">
                    <h2 class="handle">
                        <label class = "mo-sf-sync-coll-div-head"><b>${new_field[0]}</b><span style="font-size: 14px !important;font-style: italic;">  [Name: ${new_field[0]}, Type: ${new_field[1]}, Max-Length: ${new_field[2]}] </span>
                        <button type="button" style="float:right ;border:none;cursor:pointer;background:none" ><span class="dashicons dashicons-trash" onclick="deleteAttr(event,${removed_field});return false;"></span></button>
                        </label>
                    </h2>
                    <div class="mo-sf-dflex content" style="border-bottom: 0;">
                        <div class=" mo-sf-col-md-3" style="border-bottom:none">
                            <span>Field Type</span>
                        </div>
                        <div class="mo-sf-col-md-8-field-mapping">
                            <div>
                                <select class="form-control" style="width: 300px;" id="field_type_${new_field[0]}" name="field_types[]" ">
                                    <option value="standard">${def}</option> 
                                    <option value="static">${option}</option> 
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mo-sf-dflex content" style="margin-top:-20px;margin-bottom:-20px;border-bottom:none">
                        <div class="mo-sf-col-md-3">
                            <span>Select field</b></span>
                        </div>
                        <div class="mo-sf-col-md-8-field-mapping">
                            <div id="sync_sel_fields_div_${new_field[0]}">
                                
                            </div>
                        </div>
                    </div>    
                </section>
            </div>
            `
        )
        dom.children().last().children().find('select').selectWoo({})
    let field = field_map.get(new_field[0])
    selected_type = ''
        jQuery('#field_type_'+field.name).on('change',function(){
            selected_type = jQuery(this).val()
            if(selected_type == 'static'){
                if(field.type === "picklist"){
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                    .append(mo_sf_sync_show_object_mapping_for_picklist(field.name)).find('select').selectWoo({})
                }
                else
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                    .append(`<input type="text" name="wordpress_fields[]" class="custom_fields" style="width:300px;"/>`)
            }
            else{
                if (field.type === "picklist") {
                    options = `<select name="wordpress_fields[]"  style="width: 300px;">`
                    field.picklistValues.forEach(({ value, label }) => {
                        options += `<option value="${value}">${label}</option>`;
                    });
                    options += "</select>";
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
                } else if (field.type === 'boolean') {
                    options = `
                    <select name="wordpress_fields[]" style="width: 300px;">
                        <option>true</option>
                        <option>false</option>
                    </select>
                    `
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
                        .append(options)
                } else {
                    jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                        .append(`
                        <select class="form-control wp_field select2" style="width: 300px;" name="wordpress_fields[]">
                            ${mo_sf_sync_create_wp_fields()}
                        </select>
                    `).find('select').selectWoo({})
                }
            }
        })
        if (field.type === "picklist") {
            options = `<select name="wordpress_fields[]"  style="width: 300px;">`
            field.picklistValues.forEach(({ value, label }) => {
                options += `<option value="${value}">${label}</option>`;
            });
            options += "</select>";
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
        } else if (field.type === 'boolean') {
            options = `
            <select name="wordpress_fields[]" style="width: 300px;">
                <option>true</option>
                <option>false</option>
            </select>
            `
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
                .append(options)
        } else {
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                .append(`
                <select class="form-control wp_field select2" style="width: 300px;" name="wordpress_fields[]">
                    ${mo_sf_sync_create_wp_fields()}
                </select>
            `).find('select').selectWoo({})
        }
}

function mo_sf_sync_show_object_mapping_for_picklist(field_name){
    
    index = (jQuery('.sf-wp-mapping-div').children('.accordion').size()+jQuery('.new_fields_div').children().size() - 1)
    field = field_map.get(field_name);
    let newoptions = ``;
    if (field.type === "picklist") {  
        field.picklistValues.forEach(({ value, label }) => {
            newoptions += `<option value="${value}">${label}</option>`;
        });
    }
    let dom = 
        `
            <input type="button" class="mo-sf-btn-cstm" value="Add Condition" id="add_picklist_condition" style="margin-bottom: 20px;" onclick="mo_sf_sync_add_condition_for_picklist('${field_name}')">
            <div id="picklist_conditon_table_${field_name}" >
                <div></div>
                <div style="text-align:center">
                    <div class="mo-sf-dflex">
                        <div>
                            <select class="form-control wp_field" name="wordpress_fields[${index}][picklist_wp_fields][]" style="width:115px">
                                ${mo_sf_sync_create_wp_fields()}
                            </select>
                        </div>
                        <div><span>Must</span></div>
                        <div>
                            <select class="form-control operator" name="wordpress_fields[${index}][picklist_conditions][]" style="width:115px" >
                            <option value='starts-with'>Start With</option>
                            <option value='ends-with'>End With</option>
                            <option value='includes'>Includes</option>
                            <option value='must-not-include'>Not Include</option>
                            </select>
                        </div>
                        <div ><span>the value</span></div>
                        <div>
                            <input type="text" class="form-control" name="wordpress_fields[${index}][picklist_output][]" style="width:115px" required>
                        </div>
                        <div >then value synced will be</div>
                        <div >
                            <select name="wordpress_fields[${index}][picklist_result][]" id="picklist_result_${field_name}" style="width:115px">
                                ${newoptions}
                            </select>
                        </div>
                        <div>
                            <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        </div>
                    </div>
                </div>
            </div>
        `
    return dom 
}

function mo_sf_sync_add_condition_for_picklist(picklist_field){
    
    index = (jQuery('.sf-wp-mapping-div').children('.accordion').size()+jQuery('.new_fields_div').children().size() - 1) 
    table = `picklist_conditon_table_${picklist_field}`
    field = field_map.get(picklist_field);
    newoptions = ``;
    if (field.type === "picklist") {  
        field.picklistValues.forEach(({ value, label }) => {
            newoptions += `<option value="${value}">${label}</option>`;
        });
    }
    let dom= jQuery("#"+table).append(
        `
        <div style="text-align:center">
            <div class="mo_sf_sync_conditional_or">OR</div>
            <div class="mo-sf-dflex">
                <div>
                    <select class="form-control wp_field" name="wordpress_fields[${index}][picklist_wp_fields][]" style="width:115px">
                        ${mo_sf_sync_create_wp_fields()}
                    </select>
                </div>
                <div><span>Must</span></div>
                <div>
                    <select class="form-control operator" name="wordpress_fields[${index}][picklist_conditions][]" style="width:115px" >
                        <option value='starts-with'>Start With</option>
                        <option value='ends-with'>End With</option>
                        <option value='includes'>Includes</option>
                        <option value='must-not-include'>Not Include</option>
                    </select>
                </div>
                <div><span>this value</span></div>
                <div>
                    <div><input type="text" class="form-control" name="wordpress_fields[${index}][picklist_output][]" style="width:115px" required></div>
                </div>
                <div><span>then value synced will be</span></div>
                <div>
                    <select name="wordpress_fields[${index}][picklist_result][]" id="picklist_result_${picklist_field}" style="width:115px">
                        ${newoptions}
                    </select>
                </div>
                <div>
                    <button type = 'button' class="button del_row_btn mo-sf-btn-cstm" id="button-delete" onclick="deletepicklistcondition(event);return false;">X</button>
                </div>
            </div>
        </div>
        `
    )
    dom.children().last().children().find('select').selectWoo({})
}

function deletepicklistcondition(event){

    jQuery(event.target).parent().parent().parent().empty()
}

function deletesavedpickcondition(event){
    jQuery(event.target).parent().parent().empty()
}

function mo_sf_sync_change_field_type(field_name){
    let field = field_map.get(field_name)

    selected_type = document.querySelector('#field_type_'+field.name).value
    if(selected_type == 'static'){
        if(field.type === "picklist"){
            let dom = jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
            .append(mo_sf_sync_show_object_mapping_for_picklist(field.name)).find('select').selectWoo({})
        }
        else
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
            .append(`<input type="text" name="wordpress_fields[]" class="custom_fields" style="width:300px;"/>`)
    }
    else{
        if (field.type === "picklist") {
            options = `<select name="wordpress_fields[]"  style="width: 300px;">`
            field.picklistValues.forEach(({ value, label }) => {
                options += `<option value="${value}">${label}</option>`;
            });
            options += "</select>";
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
        } else if (field.type === 'boolean') {
            options = `
            <select name="wordpress_fields[]" style="width: 300px;">
                <option>true</option>
                <option>false</option>
            </select>
            `
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end().append(options).find('select').selectWoo({})
                .append(options)
        } else {
            jQuery('#sync_sel_fields_div_'+field.name).children().remove().end()
                .append(`
                <select class="form-control wp_field select2" style="width: 300px;" name="wordpress_fields[]">
                    ${mo_sf_sync_create_wp_fields()}
                </select>
            `).find('select').selectWoo({})
        }
    }
}

function mo_sf_sync_open_window_for_authorization(app_type){
    home_url = document.getElementById('mo_sf_sync_home_url').value
    url = home_url+`?option=authorization_flow&app_type=${app_type}`
    window.open(url,'Connect To Salesforce',"scrollbars=1 width=800, height=600")
}

function mo_sf_sync_make_email_editable(){
    email_sent = document.getElementById('email-sent-id')
    email = email_sent.innerText
    email_sent.innerHTML = `<input type='text' required id="mo_sf_sync_demo_email" name='mo_sf_sync_demo_email' class='mo_sf_sync_email_input' value='${email}'>`
}

function mo_sf_sync_show_business_uid(event){
    if(event.target.checked === true){
        document.getElementById('pardot_business').hidden = false;
        document.getElementById('pardot_env').hidden = false
        document.getElementById('pardot_business_uid').setAttribute('required','')
    }
    else{
        document.getElementById('pardot_business').hidden = true;
        document.getElementById('pardot_env').hidden = true
        document.getElementById('pardot_business_uid').removeAttribute('required')
    }
}

function mo_sf_sync_assign_url_for_buid(){
    jQuery('.mo-sf-sync-pid-button').attr("href", jQuery('input[name=env_link]').val()+"/lightning/setup/PardotAccountSetup/home");
}