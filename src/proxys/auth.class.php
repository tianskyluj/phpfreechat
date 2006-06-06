<?php
/**
 * auth.class.php
 *
 * Copyright © 2006 Stephane Gully <stephane.gully@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details. 
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301  USA
 */
require_once dirname(__FILE__)."/../pfci18n.class.php";
require_once dirname(__FILE__)."/../pfcuserconfig.class.php";
require_once dirname(__FILE__)."/../pfcproxycommand.class.php";

/**
 * pfcProxyCommand_auth
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcProxyCommand_auth extends pfcProxyCommand
{
  function run(&$xml_reponse, $clientid, $param, $sender, $recipient, $recipientid)
  {
    $c =& $this->c;
    $u =& $this->u;

    // protect admin commands
    $admincmd = array("kick", "ban", "unban", "op", "deop", "debug", "rehash");
    if ( in_array($this->name, $admincmd) )
    {
      $container =& $c->getContainerInstance();
      $nickid = $container->getNickId($sender);
      $isadmin = $container->getMeta("isadmin", "nickname", $nickid);
      if (!$isadmin)
      {
        $xml_reponse->addScript("alert('".addslashes(_pfc("You are not allowed to run '%s' command", $this->name))."');");
        return;
      }
    }

    // protect channel from the banished users
    if ($this->name == "join")
    {
      // check the user is not listed in the banished channel list
      $container   =& $c->getContainerInstance();
      $channame    = $param;
      $chanid      = pfcCommand_join::GetRecipientId($channame);
      $banlist     = $container->getMeta("banlist_nickid", "channel", $chanid);
      if ($banlist == NULL) $banlist = array(); else $banlist = unserialize($banlist);

      $nickid = $container->getNickId($u->nick);
      if (in_array($nickid,$banlist))
      {
        
        // the user is banished, show a message and don't forward the /join command
        $msg = _pfc("Can't join %s because you are banished", $param);
        $xml_reponse->addScript("pfc.handleResponse('".$this->proxyname."', 'ban', '".addslashes($msg)."');");
        return;
      }
    }

    // disallow to change nickname if frozen_nick is true
    if ($this->name == "nick")
    {
      if ($param != $c->nick &&
          $c->frozen_nick == true)
      {
        $msg = _pfc("You are not allowed to change your nickname", $param);
        $xml_reponse->addScript("pfc.handleResponse('".$this->proxyname."', 'nick', '".addslashes($msg)."');");
        return;
      }
    }
    
    // forward the command to the next proxy or to the final command
    $this->next->run(&$xml_reponse, $clientid, $param, $sender, $recipient, $recipientid);
  }
}

?>