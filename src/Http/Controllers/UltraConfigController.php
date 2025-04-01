<?php

namespace Ultra\UltraConfigManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Ultra\UltraConfigManager\UltraConfigManager;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraLogManager\Facades\UltraLog;
use Ultra\ErrorManager\Facades\UltraError;

class UltraConfigController extends Controller
{
    protected $uconfig;
    protected $configDao;

    public function __construct(UltraConfigManager $uconfig, ConfigDaoInterface $configDao)
    {
        $this->uconfig = $uconfig;
        $this->configDao = $configDao;
    }

    public function index()
    {
        $configs = $this->configDao->getAllConfigs();
        return view('vendor.uconfig.index', compact('configs'));
    }

    public function create()
    {
        return view('vendor.uconfig.create');
    }

    public function store(Request $request, $userId = null)
    {
        // Validazione degli input
        $key = $request->input('key');
        $value = $request->input('value');
        if (empty($key) || !is_string($key)) {
            $exception = new \Exception('Invalid or missing key in request');
            return UltraError::handle('INVALID_INPUT', ['param' => 'key'], $exception);
        }
        if (empty($value) || !is_string($value)) {
            $exception = new \Exception('Invalid or missing value in request');
            return UltraError::handle('INVALID_INPUT', ['param' => 'value'], $exception);
        }

        $data = $request->validate([
            'key' => 'required|unique:uconfig,key',
            'value' => 'required',
            'category' => 'nullable|in:' . implode(',', array_map(fn($case) => $case->value, CategoryEnum::cases())),
            'note' => 'nullable|string',
        ]);

        UltraLog::info('UCM Controller', 'store: newValue: request->key:' . $request->key, [
            'value' => $request->value,
            'category' => $request->category,
            'user_id' => $userId ?? 'N/A',
        ]);

        $config = $this->configDao->createConfig($data);
        $this->configDao->createVersion($config, 1);
        $this->configDao->createAudit($config->id, 'created', null, $config->value, $userId);

        $this->uconfig->refreshConfigCache($data['key']);
        return redirect()->route('uconfig.index')->with('success', 'Configurazione aggiunta con successo.');
    }

    public function edit($id)
    {
        $config = $this->configDao->getConfigById($id);
        $audits = $this->configDao->getAuditsByConfigId($id);
        return view('vendor.uconfig.edit', compact('config', 'audits'));
    }

    public function update(Request $request, $id, $userId = null)
    {
        $config = $this->configDao->getConfigById($id);

        // Validazione degli input
        $value = $request->input('value');
        if (empty($value) || !is_string($value)) {
            $exception = new \Exception('Invalid or missing value in request');
            return UltraError::handle('INVALID_INPUT', ['param' => 'value'], $exception);
        }

        $data = $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
            'category' => 'nullable|in:' . implode(',', array_map(fn($case) => $case->value, CategoryEnum::cases())),
            'note' => 'nullable|string',
        ]);

        $oldValue = $config->value;

        UltraLog::info('UCM Controller', 'update: updating configuration', [
            'key' => $config->key,
            'old_value' => $oldValue,
            'new_value' => $data['value'],
            'category' => $data['category'],
            'user_id' => $userId ?? 'N/A',
        ]);

        $config = $this->configDao->updateConfig($config, $data);
        $latestVersion = $this->configDao->getLatestVersion($config->id);
        $this->configDao->createVersion($config, $latestVersion + 1);
        $this->configDao->createAudit($config->id, 'updated', $oldValue, $config->value, $userId);

        $this->uconfig->refreshConfigCache($config->key);
        return redirect()->route('uconfig.index')->with('success', 'Configurazione aggiornata con successo.');
    }

    public function destroy($id, $userId = null)
    {
        $config = $this->configDao->getConfigById($id);
        $oldValue = $config->value;

        UltraLog::info('UCM Controller', 'destroy: deleting configuration', [
            'key' => $config->key,
            'value' => $config->value,
            'user_id' => $userId ?? 'N/A',
        ]);

        $this->configDao->createAudit($config->id, 'deleted', $oldValue, null, $userId);
        $this->configDao->deleteConfig($config);

        $this->uconfig->refreshConfigCache($config->key);
        return redirect()->route('uconfig.index')->with('success', 'Configurazione eliminata con successo.');
    }

    public function audit($id)
    {
        $config = $this->configDao->getConfigById($id);
        $audits = $this->configDao->getAuditsByConfigId($id);
        return view('vendor.uconfig.audit', compact('config', 'audits'));
    }
}