<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

// Because this is a reset of the persons password make the form a little more secure
?>
<?php Error::display('general'); ?>
<form method="post" name="preferences" action="<?php echo AmpConfig::get('web_path'); ?>/preferences.php?action=update_user" enctype="multipart/form-data">
    <table class="tabledata" cellspacing="0" cellpadding="0">
        <tr>
            <td><?php echo T_('Name'); ?>:</td>
            <td>
                <input type="text" name="fullname" value="<?php echo scrub_out($client->fullname); ?>" />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('E-mail'); ?>:</td>
            <td>
                <input type="text" name="email" value="<?php echo scrub_out($client->email); ?>" />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('New Password'); ?>:</td>
            <td>
                <?php Error::display('password'); ?>
                <input type="password" name="password1" />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Confirm Password'); ?>:</td>
            <td>
                <input type="password" name="password2" />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Clear Stats'); ?>:</td>
            <td>
                <input type="checkbox" name="clear_stats" value="1" />
            </td>
        </tr>
    </table>
    <div class="formValidation">
            <input type="hidden" name="user_id" value="<?php echo scrub_out($client->id); ?>" />
            <?php echo Core::form_register('update_user'); ?>
            <input type="hidden" name="tab" value="<?php echo scrub_out($_REQUEST['tab']); ?>" />
            <input class="button" type="submit" value="<?php echo T_('Update Account'); ?>" />
    </div>
</form>
