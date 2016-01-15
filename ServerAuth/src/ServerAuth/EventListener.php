<?php

/*
 * ServerAuth (v2.11) by EvolSoft
 * Developer: EvolSoft (Flavius12)
 * Website: http://www.evolsoft.tk
 * Date: 31/08/2015 05:39 PM (UTC)
 * Copyright & License: (C) 2015 EvolSoft
 * Licensed under MIT (https://github.com/EvolSoft/ServerAuth/blob/master/LICENSE)
 */

namespace ServerAuth;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;

use ServerAuth\ServerAuth;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class EventListener implements Listener {
	
	public function __construct(ServerAuth $plugin){
		$this->plugin = $plugin;
	}
	
	public function onPreLogin(PlayerPreLoginEvent $event){
		//Restore default messages
		ServerAuth::getAPI()->enableLoginMessages(true);
		ServerAuth::getAPI()->enableRegisterMessages(true);
		$cfg = $this->plugin->getConfig()->getAll();
		if($cfg['force-single-auth']){
			$player = $event->getPlayer();
			$count = 0;
			foreach($this->plugin->getServer()->getOnlinePlayers() as $pl){
				if(strtolower($pl->getName()) == strtolower($player->getName())){
					$count++;
				}
			}
			if($count > 1){
				$player->close("", $this->plugin->translateColors("&", ServerAuth::getAPI()->getConfigLanguage()->getAll()["single-auth"]), $this->plugin->translateColors("&", ServerAuth::getAPI()->getConfigLanguage()->getAll()["single-auth"]), false);
				$event->setCancelled();
			}
		}
	}
	
    public function onJoin(PlayerJoinEvent $event){
    	$player = $event->getPlayer();
    	$cfg = $this->plugin->getConfig()->getAll();
    	if($cfg["show-join-message"]){
    		$player->sendMessage($this->plugin->translateColors("&", $cfg["prefix"] . ServerAuth::getAPI()->getConfigLanguage()->getAll()["join-message"]));
    	}
    	if(ServerAuth::getAPI()->isPlayerAuthenticated($player)){
    		//IP Authentication
    		if($cfg["IPLogin"]){
    			$playerdata = ServerAuth::getAPI()->getPlayerData($player->getName());
    			if($playerdata["ip"] == $player->getAddress()){
    				ServerAuth::getAPI()->authenticatePlayer($player, $playerdata["password"], false);
    				$player->sendMessage($this->plugin->translateColors("&", $cfg["prefix"] . ServerAuth::getAPI()->getConfigLanguage()->getAll()["login"]["ip-login"]));
    			}else{
    				ServerAuth::getAPI()->deauthenticatePlayer($event->getPlayer());
    			}
    		}else{
    			ServerAuth::getAPI()->deauthenticatePlayer($event->getPlayer());
    		}
    	}
    	if(!ServerAuth::getAPI()->isPlayerRegistered($player->getName()) && ServerAuth::getAPI()->areRegisterMessagesEnabled()){
    		if($cfg["register"]["password-confirm-required"]){
    			$player->sendMessage($this->plugin->translateColors("&", $cfg["prefix"] . ServerAuth::getAPI()->getConfigLanguage()->getAll()["register"]["message-conf"]));
    		}else{
    			$player->sendMessage($this->plugin->translateColors("&", $cfg["prefix"] . ServerAuth::getAPI()->getConfigLanguage()->getAll()["register"]["message"]));
    		}
    	}else{
    		if(!ServerAuth::getAPI()->isPlayerAuthenticated($player) && ServerAuth::getAPI()->areLoginMessagesEnabled()){
    			$player->sendMessage($this->plugin->translateColors("&", $cfg["prefix"] . ServerAuth::getAPI()->getConfigLanguage()->getAll()["login"]["message"]));
    		}
    	}
    }
    
    public function onPlayerMove(PlayerMoveEvent $event){
    	if(!$this->plugin->getConfig()->getAll()["allow-move"]){
    		if(!ServerAuth::getAPI()->isPlayerAuthenticated($event->getPlayer())){
    			$event->setCancelled();
    		}
    	}
    }
    
    public function onItemConsume(PlayerItemConsumeEvent $event){
    	if($this->plugin->getConfig()->getAll()["block-all-events"]){
    		if(!ServerAuth::getAPI()->isPlayerAuthenticated($event->getPlayer())){
    			$event->setCancelled(true);
    		}
    	}
    }
    
    public function onPlayerChat(PlayerChatEvent $event){
    	if($this->plugin->getConfig()->getAll()["block-chat"]){
    		if(!ServerAuth::getAPI()->isPlayerAuthenticated($event->getPlayer())){
    			$event->setCancelled(); //Cancel message
    		}
    		$recipients = $event->getRecipients();
    		foreach($recipients as $key => $player){
    			if($player instanceof Player){
    				if(!ServerAuth::getAPI()->isPlayerAuthenticated($player)){
    					$message[] = $key;
    					foreach($message as $messages){
    						unset($recipients[$key]);
    						$event->setRecipients(array_values($recipients));
    					}
    				}
    			}
    		}
    	}
    }
    
    public function onPlayerCommand(PlayerCommandPreprocessEvent $event){
        if($this->plugin->getConfig()->getAll()["block-commands"]){
    		if(!ServerAuth::getAPI()->isPlayerAuthenticated($event->getPlayer())){
    			$command = strtolower($event->getMessage());
    			if($command{0} == "/"){
    				$command = explode(" ", $command);
    				if($command[0] != "/login" && $command[0] != "/register" && $command[0] != "/reg"){
    					$event->setCancelled();
    				}
    			}
    		}
    	}
    }
    
    public function onPlayerInteract(PlayerInteractEvent $event){
    	if(!ServerAuth::getAPI()->isPlayerAuthenticated($event->getPlayer())){
    		$event->setCancelled();
    	}
    }

    public function onEntityDamage(EntityDamageEvent $event){
    		$player = $event->getEntity();
    		if($player instanceof Player){
    			if(!ServerAuth::getAPI()->isPlayerAuthenticated($player)){
    				$event->setCancelled();
    			}
    		}
    	if($event instanceof EntityDamageByEntityEvent){
    		$damager = $event->getDamager();
    		if($damager instanceof Player){
    			if(!ServerAuth::getAPI()->isPlayerAuthenticated($damager)){
    				$event->setCancelled();
    			}
    		}
    	}
    }

    public function onDropItem(PlayerDropItemEvent $event){
    	if($this->plugin->getConfig()->getAll()["block-all-events"]){
    		if(!ServerAuth::getAPI()->isPlayerAuthenticated($event->getPlayer())){
    			$event->setCancelled();
    		}
    	}
    }

    public function onAwardAchievement(PlayerAchievementAwardedEvent $event){
    	if($this->plugin->getConfig()->getAll()["block-all-events"]){
    		if(!ServerAuth::getAPI()->isPlayerAuthenticated($event->getPlayer())){
    			$event->setCancelled();
    		}
    	}
    }
}
?>
