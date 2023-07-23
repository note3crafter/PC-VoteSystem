<?php

//  ╔═════╗ ╔═════╗ ╔═════╗     ╔═╗ ╔═════╗ ╔═════╗ ╔═════╗      ╔═════╗ ╔═════╗ ╔═════╗ ╔═════╗
//  ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═╗ ║     ║ ║ ║ ╔═══╝ ║ ╔═══╝ ╚═╗ ╔═╝      ║ ╔═══╝ ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═══╝
//  ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ║ ║ ║     ║ ║ ║ ╚══╗  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╚═╝ ║ ║ ╚══╗
//  ║ ╔═══╝ ║ ╔╗ ╔╝ ║ ║ ║ ║ ╔═╗ ║ ║ ║ ╔══╝  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╔╗ ╔╝ ║ ╔══╝
//  ║ ║     ║ ║╚╗╚╗ ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ╚═══╗ ║ ╚═══╗   ║ ║        ║ ╚═══╗ ║ ╚═╝ ║ ║ ║╚╗╚╗ ║ ╚═══╗
//  ╚═╝     ╚═╝ ╚═╝ ╚═════╝ ╚═════╝ ╚═════╝ ╚═════╝   ╚═╝        ╚═════╝ ╚═════╝ ╚═╝ ╚═╝ ╚═════╝
//  Easy to Use! Written in Love! Project Core by TheNote\RetroRolf\Rudolf2000\note3crafter

namespace TheNote\VoteSystem\commands;

use pmmp\thread\ThreadSafeArray;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use TheNote\core\CoreAPI;
use TheNote\core\utils\Permissions;
use TheNote\VoteSystem\Main;
use TheNote\VoteSystem\task\RequestThread;
use TheNote\VoteSystem\utils\ServerList;

class VoteCommand extends Command
{
    private Main $plugin;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        parent::__construct("vote", $this->plugin->getCFG("Prefix") . $this->plugin->getCFG("VoteCommand"), "/vote");
        $this->setPermission(Permissions::$defaultperm);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $api = new CoreAPI();
        if (!$sender instanceof Player) {
            $sender->sendMessage($api->getCommandPrefix("Error") . $api->getCommandPrefix("CommandIngame"));
            return false;
        }
        $this->plugin->queue[] = strtolower($sender->getName());
        $requests = [];
        foreach ($this->plugin->lists as $list) {
            if (isset($list["check"]) && isset($list["claim"])) {
                $requests[] = new ServerList($list["check"], $list["claim"]);
            }
        }
        Server::getInstance()->getAsyncPool()->submitTask(new RequestThread(strtolower($sender->getName()), igbinary_serialize($requests)));
        return true;
    }
}