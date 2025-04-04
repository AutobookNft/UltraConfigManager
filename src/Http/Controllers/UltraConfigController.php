<?php

namespace Ultra\UltraConfigManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Ultra\UltraConfigManager\UltraConfigManager;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraLogManager\Facades\UltraLog;
use Ultra\ErrorManager\Facades\UltraError;

/**
 * UltraConfigController
 *
 * This controller handles HTTP requests related to configuration entries.
 * It supports creation, edition, deletion, versioning, audit tracking and error handling,
 * all integrated into the UltraConfigManager ecosystem.
 */
class UltraConfigController extends Controller
{
    protected $uconfig;
    protected $configDao;

    /**
     * Constructor for the controller.
     *
     * @param UltraConfigManager $uconfig       The configuration manager instance.
     * @param ConfigDaoInterface $configDao     DAO implementation for config storage.
     */
    public function __construct(UltraConfigManager $uconfig, ConfigDaoInterface $configDao)
    {
        $this->uconfig = $uconfig;
        $this->configDao = $configDao;
    }

    /**
     * Display the list of all configuration entries.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $configs = $this->configDao->getAllConfigs();
        return view('uconfig::index', compact('configs'));
    }

    /**
     * Show the form for creating a new configuration entry.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
    
        // Prepara i dati per la vista
        $categories = CategoryEnum::translatedOptions();
        
        // Restituisci la vista con i dati
        return view('uconfig::create', compact('categories'));
    }

    /**
     * Store a new configuration entry, including versioning and auditing.
     *
     * @param Request $request     The incoming request.
     * @param int|null $userId     Optional user ID for audit tracking.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $userId = null)
    {
        // Input validation (manual + formal)
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
        return redirect()->route('uconfig.index')->with('success', __('uconfig::uconfig.success.created'));
    }

    /**
     * Show the form to edit an existing configuration entry.
     *
     * @param int $id  The ID of the configuration.
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $config = $this->configDao->getConfigById($id);
        $audits = $this->configDao->getAuditsByConfigId($id);
        return view('uconfig::edit', compact('config', 'audits'));
    }

    /**
     * Update an existing configuration entry, with versioning and audit logging.
     *
     * @param Request $request
     * @param int $id
     * @param int|null $userId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id, $userId = null)
    {
        $config = $this->configDao->getConfigById($id);

        // Input validation (manual + formal)
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
        return redirect()->route('uconfig.index')->with('success', __('uconfig::uconfig.success.updated'));
    }

    /**
     * Delete a configuration entry, while logging and auditing the operation.
     *
     * @param int $id
     * @param int|null $userId
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Show the audit log for a specific configuration.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function audit($id)
    {
        $config = $this->configDao->getConfigById($id);
        $audits = $this->configDao->getAuditsByConfigId($id);
        return view('uconfig::audit', compact('config', 'audits'));
    }
}
