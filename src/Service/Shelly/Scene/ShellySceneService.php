<?php

declare(strict_types=1);

namespace App\Service\Shelly\Scene;

use App\Enum\ShellyComponentType;
use App\Exception\ShellyWriteOutcomeUnknownException;
use App\Model\Device\Light\LocalWhiteLightDevice;
use App\Model\Device\Relay\Relay;
use App\Model\Scene\SceneAction;
use App\Model\Scene\SceneRunResult;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyLedWriter;
use App\Service\Shelly\Local\ShellySwitchWriter;

final readonly class ShellySceneService
{
    public function __construct(
        private SceneRegistry        $scenes,
        private ShellyDeviceRegistry $devices,
        private ShellyLedWriter      $ledWriter,
        private ShellySwitchWriter   $switchWriter,
    ) {}

    public function trigger(string|int $sceneId): SceneRunResult
    {
        $scene     = $this->scenes->get($sceneId);
        $completed = [];
        $failed    = [];

        foreach ($scene->getActions() as $action) {
            try {
                $this->execute($action);

                $completed[] = [
                    'device'     => $action->deviceName,
                    'on'         => $action->on,
                    'brightness' => $action->brightness,
                ];
            } catch (\Exception $exception) {
                $failed[] = [
                    'device'  => $action->deviceName,
                    'outcome' => $exception instanceof ShellyWriteOutcomeUnknownException ? 'unknown' : 'failed',
                    'reason'  => $exception->getMessage(),
                ];
            }
        }

        return new SceneRunResult($scene->getId(), $completed, $failed);
    }

    private function execute(SceneAction $action): void
    {
        $device = $this->devices->getDevice($action->deviceName);

        if ($device instanceof LocalWhiteLightDevice) {
            if ($action->on) {
                $this->ledWriter->turnOn($device, $action->brightness);
            } else {
                $this->ledWriter->turnOff($device);
            }

            return;
        }

        if ($device instanceof Relay && $device->getComponentType() === ShellyComponentType::Switch) {
            if ($action->brightness !== null) {
                throw new \LogicException(sprintf('Brightness is not supported by relay "%s".', $action->deviceName));
            }

            $this->switchWriter->setVerified($action->deviceName, $action->on);

            return;
        }

        throw new \LogicException(sprintf('Device "%s" is not supported by local lighting scenes.', $action->deviceName));
    }
}
