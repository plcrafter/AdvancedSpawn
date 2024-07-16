<?php

declare(strict_types=1);

namespace plcrafter\AdvancedSpawn;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\Config;
use pocketmine\Server;

class Main extends PluginBase {
    
    private Config $config;
    private Server $server;
    
    public function onEnable(): void {
        $this->config = $this->getConfig();
        $dataFolder = $this->getDataFolder();
        if (file_exists($dataFolder . "config.yml") && $this->config->get("config-version") !== "1") {
            rename($dataFolder . "config.yml", $dataFolder . "deprecated_config.yml");
        }
        $this->saveDefaultConfig();
        $this->server = $this->getServer();
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        $messages = $this->config->get("messages");
        $messagePrefix = $messages["message-prefix"];
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be use in game");
            return true;
        }
        switch($command->getName()) {
            case "spawn":
                if (!$this->config->exists("spawn-location")) {
                    $sender->sendMessage($messagePrefix . $messages["no-spawn-set"]);
                    return true;
                }
                if (!isset($args[0]) || $args[0] == $sender->getName()) {
                    $this->teleportToSpawn($sender);
                    $sender->sendMessage($messagePrefix . $messages["teleport-to-spawn"]);
                    return true;
                }
                if (!$sender->hasPermission("advancedspawn.permission.spawn.other")) {
                    $sender->sendMessage($messagePrefix . $messages["no-permission-to-teleport-other-players-to-spawn"]);
                    return true;
                }
                $result = $this->server->getPlayerExact($args[0]);
                if ($result === null) {
                    $sender->sendMessage($messagePrefix . $messages["player-not-found"]);
                    return true;
                }
                $player = $result;
                if ($player->hasPermission("advancedspawn.permission.spawn.other.bypass")) {
                    $sender->sendMessage($messagePrefix . $messages["player-cannot-be-teleported-to-spawn"]);
                    return true;
                }
                $this->teleportToSpawn($player);
                $sender->sendMessage($messagePrefix . str_replace(["{playerName}"], [$player->getName()], $messages["teleport-other-player-to-spawn"]));
                return true;
            case "setspawn":
                $senderLocation = $sender->getLocation();
                $this->config->set("spawn-location", ["x" => $senderLocation->getX(), "y" => $senderLocation->getY(), "z" => $senderLocation->getZ(), "world" => $senderLocation->getWorld()->getFolderName(), "yaw" => $senderLocation->getYaw(), "pitch" => $senderLocation->getPitch()]);
                $sender->sendMessage($messagePrefix . $messages["spawn-set"]);
                $this->config->save();
                return true;
            case "delspawn":
                $this->config->remove("spawn-location");
                $sender->sendMessage($messagePrefix . $messages["spawn-deleted"]);
                $this->config->save();
                return true;
        }
    }
    
    public function teleportToSpawn(Player $player): void {
        $spawnLocation = $this->config->get("spawn-location");
        $player->teleport(new Position($spawnLocation["x"], $spawnLocation["y"], $spawnLocation["z"], $this->server->getWorldManager()->getWorldByName($spawnLocation["world"])), $spawnLocation["yaw"], $spawnLocation["pitch"]);
    }
}