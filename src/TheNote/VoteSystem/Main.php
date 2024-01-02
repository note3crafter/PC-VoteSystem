<?php

//  ╔═════╗ ╔═════╗ ╔═════╗     ╔═╗ ╔═════╗ ╔═════╗ ╔═════╗      ╔═════╗ ╔═════╗ ╔═════╗ ╔═════╗
//  ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═╗ ║     ║ ║ ║ ╔═══╝ ║ ╔═══╝ ╚═╗ ╔═╝      ║ ╔═══╝ ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═══╝
//  ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ║ ║ ║     ║ ║ ║ ╚══╗  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╚═╝ ║ ║ ╚══╗
//  ║ ╔═══╝ ║ ╔╗ ╔╝ ║ ║ ║ ║ ╔═╗ ║ ║ ║ ╔══╝  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╔╗ ╔╝ ║ ╔══╝
//  ║ ║     ║ ║╚╗╚╗ ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ╚═══╗ ║ ╚═══╗   ║ ║        ║ ╚═══╗ ║ ╚═╝ ║ ║ ║╚╗╚╗ ║ ╚═══╗
//  ╚═╝     ╚═╝ ╚═╝ ╚═════╝ ╚═════╝ ╚═════╝ ╚═════╝   ╚═╝        ╚═════╝ ╚═════╝ ╚═╝ ╚═╝ ╚═════╝
//  Easy to Use! Written in Love! Project Core by TheNote\RetroRolf\Rudolf2000\note3crafter

namespace TheNote\VoteSystem;

use pocketmine\block\VanillaBlocks;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use TheNote\core\CoreAPI;
use TheNote\core\utils\DiscordAPI;
use TheNote\VoteSystem\commands\VoteCommand;
use TheNote\VoteSystem\utils\VoteUtils;

class Main extends PluginBase
{
    public static $plname = "PC-VoteSystem";
    public array $queue = [];
    private mixed $commands;
    public bool $debug;
    public array $lists;

    public function onLoad(): void
    {
        $projectcore = $this->getServer()->getPluginManager()->getPlugin("ProjectCore");
        if ($projectcore === null) {
            $this->getLogger()->alert("This Plugin need ProjectCore! Please install ProjectCore before Using this Plugin!");
            $this->getServer()->shutdown();
        }
        @mkdir($this->getDataFolder() . "Lang");
        @mkdir($this->getDataFolder() . "Key");
        $this->saveResource("Lang/LangDEU.json");
        $this->saveResource("Lang/LangENG.json");
        $this->saveResource("Lang/LangESP.json");
        $this->saveResource("Config.yml");
    }
    public function onEnable(): void
    {
        $this->reload();
        $this->getServer()->getCommandMap()->register("vote", new VoteCommand($this));
    }

    public function getLang(string $player, $langkey) {
        $api = new CoreAPI();
        $lang = new Config($this->getDataFolder() . "Lang/Lang" . $api->getUser($player, "language") . ".json", Config::JSON);
        return $lang->get($langkey);
    }
    public function getCFG($key) {
        $cfg = new Config($this->getDataFolder() . "Config.yml", Config::YAML);
        return $cfg->get($key);
    }

    public function reload()
    {
        $this->saveDefaultConfig();
        $this->lists = [];
        $c = new Config($this->getDataFolder() . "Config.yml", Config::YAML);
        $config = $c->getAll();
        $this->commands = $config["Commands"];
        foreach (scandir($this->getDataFolder() . "Key/") as $file) {
            $ext = explode(".", $file);
            $ext = (count($ext) > 1 && isset($ext[count($ext) - 1]) ? strtolower($ext[count($ext) - 1]) : "");
            if ($ext == "vrc") {
                $this->lists[] = json_decode(file_get_contents($this->getDataFolder() . "Key/$file"), true);
            }
        }

        $this->reloadConfig();
        $config = $this->getConfig()->getAll();
        $this->debug = isset($config["Debug"]) && ($config["Debug"] === true);

    }
    public function rewardPlayer($player, $multiplier)
    {
        $api = new CoreAPI();
        $chatprefix = $api->getDiscord("chatprefix");
        $prefix = $this->getCFG("Prefix");
        if (!$player instanceof Player) {
            return;
        }
        if ($multiplier < 1) {
            $player->sendMessage($prefix . $this->getLang($player->getName(), "NotVotet"));
            return;
        }
        foreach($this->commands as $command) {
            $this->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), str_replace([
                "{player}",
                "{name}",
                "{X}",
                "{Y}",
                "{Z}"
            ], [
                $player,
                $player->getName(),
                $player->getPosition()->getX(),
                $player->getPosition()->getY(),
                $player->getPosition()->getZ()
            ], VoteUtils::translateColors($command)));
        }
        $player->sendMessage($prefix . $this->getLang($player->getName(),"VoteSucces"));
        $this->getServer()->broadcastMessage($prefix . str_replace("{player}", $player->getNameTag(), $this->getCFG("VoteBC")));
        if ($api->modules("StatsSystem") === true) {
            $api->addVotePoints($player, 1);
            $api->addServerStats("votes", 1);
        }
        if ($api->modules("EconomySystem") === true) {
            $api->addMoney($player, $this->getCFG("Money"));
        }
        if ($api->modules("DiscordSystem") === true) {
            $ar = getdate();
            $time = $ar['hours'] . ":" . $ar['minutes'];
            $format = str_replace("{dcprefix}", $chatprefix, $api->getDiscord("VoteMSG"));
            $msg = str_replace("{time}", $time, str_replace("{player}", $player->getName(), $format));
            $dc = new DiscordAPI();
            $dc->sendMessage($format, $msg);
        }
    }
}
