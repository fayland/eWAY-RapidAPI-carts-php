<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
    <div id="Eway" class="tab_content">
        <h3>{$TITLE}</h3>
        <fieldset><legend>{$LANG.module.cubecart_settings}</legend>
            <div><label for="status">{$LANG.common.status}</label><span><input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" /></span></div>
            <div><label for="default">{$LANG.common.default}</label><span><input type="hidden" name="module[default]" id="default" class="toggle" value="{$MODULE.default}" /></span></div>
            <div><label for="description">{$LANG.common.description}</label><span><input name="module[desc]" id="desc" class="textbox" type="text" value="{$MODULE.desc}" /></span></div>

            <div><label for="testmode">{$LANG.module.mode_test}</label>
                <span>
                    <select name="module[testMode]" id="testmode">
                        <option value="1" {$SELECT_testMode_1}>{$LANG.common.yes}</option>
                        <option value="0" {$SELECT_testMode_0}>{$LANG.common.no}</option>
                    </select>
                </span>
            </div>

            </fieldset>
            <fieldset>
                <legend>{$LANG.EwayRapid.settings}</legend>
                <p>{$LANG.module.3rd_party_settings_desc}</p>

                <div><label for="eway_username">{$LANG.EwayRapid.username}</label><span><input name="module[eway_username]" id="eway_username" class="textbox" type="text" value="{$MODULE.eway_username}" /></span></div>

                <div><label for="eway_password">{$LANG.EwayRapid.password}</label><span><input name="module[eway_password]" id="eway_password" class="textbox" type="text" value="{$MODULE.eway_password}" /></span></div>

                <div><label for="payment_type">Payment Type</label>
                <span>
                    <input type='checkbox' name='module[eway_payment_type_visa]' value='1' {$CHECKED_eway_payment_type_visa_1} /> CC - Visa
                    <input type='checkbox' name='module[eway_payment_type_mastercard]' value='1' {$CHECKED_eway_payment_type_mastercard_1} /> CC - MasterCard
                    <input type='checkbox' name='module[eway_payment_type_diners]' value='1' {$CHECKED_eway_payment_type_diners_1} /> CC - Diners Clue
                    <input type='checkbox' name='module[eway_payment_type_jcb]' value='1' {$CHECKED_eway_payment_type_jcb_1} /> CC - JCB
                    <input type='checkbox' name='module[eway_payment_type_amex]' value='1' {$CHECKED_eway_payment_type_amex_1} /> CC - Amex
                </span>
                </div>
                <div><label>&nbsp;</label>
                <span>
                    <input type='checkbox' name='module[eway_payment_type_paypal]' value='1' {$CHECKED_eway_payment_type_paypal_1} /> PayPal
                    <input type='checkbox' name='module[eway_payment_type_masterpass]' value='1' {$CHECKED_eway_payment_type_masterpass_1} /> MasterPass
                    <input type='checkbox' name='module[eway_payment_type_vme]' value='1' {$CHECKED_eway_payment_type_vme_1} /> V.me By Visa
                </span>
                </div>
            </div>
            </fieldset>
        </div>
        {$MODULE_ZONES}
        <div class="form_control">
            <input type="submit" name="save" value="{$LANG.common.save}" />
        </div>

    <input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>