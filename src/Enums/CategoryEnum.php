<?php

namespace Ultra\UltraConfigManager\Enums;

enum CategoryEnum: string
{
    case System = 'system';
    case Application = 'application';
    case Security = 'security';
    case Performance = 'performance';
    case None = ''; // Per rappresentare l'assenza di categoria (opzionale)
    
    /**
     * Ottiene la chiave di traduzione per una determinata categoria.
     *
     * @return string La chiave di traduzione
     */
    public function translationKey(): string
    {
        return 'uconfig::uconfig.categories.' . $this->value;
    }
    
    /**
     * Ottiene il nome tradotto della categoria.
     *
     * @return string Il nome tradotto
     */
    public function translatedName(): string
    {
        if ($this === self::None) {
            return __('uconfig::uconfig.categories.none');
        }
        
        return __($this->translationKey());
    }
    
    /**
     * Ottiene un array di tutte le categorie con i loro nomi tradotti.
     *
     * @return array Array associativo di [valore => nome tradotto]
     */
    public static function translatedOptions(): array
    {
        $options = [];
        
        foreach (self::cases() as $case) {
            if ($case !== self::None) { // Escludiamo None dalle opzioni se necessario
                $options[$case->value] = $case->translatedName();
            }
        }
        
        return $options;
    }
}