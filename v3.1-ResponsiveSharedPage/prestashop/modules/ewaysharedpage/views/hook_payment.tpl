<p class="payment_module">
    <a href="{$this_path_ssl}eway.php" title="{l s='Pay with Eway' mod='ewaysharedpage'}">
        <img src="{$module_dir}images/PayWithCC.png" alt="{l s='Pay with Eway' mod='ewaysharedpage'}" />
    </a>
    <p class="cart_navigation submit" style="margin-top: -60px">
        <input type="submit" onclick="window.location='{$this_path_ssl}eway.php'; return false;" name="processPayment" value="{l s='Process Payment' mod='ewaysharedpage'}" class="exclusive" />
    </p>
</p>
