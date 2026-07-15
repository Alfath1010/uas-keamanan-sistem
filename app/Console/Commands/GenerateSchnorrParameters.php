<?php

namespace App\Console\Commands;

use App\Services\Security\Schnorr\SchnorrParameterGenerator;
use App\Services\Security\Schnorr\SchnorrParameterProviderInterface;
use Illuminate\Console\Command;

/**
 * One-time deployment setup step, per crypto_design.md §4.4.2.
 *
 * Regenerating parameters after users have registered Schnorr keys
 * would invalidate every existing key/signature, so this command
 * refuses to overwrite an existing parameter set unless --force is
 * passed.
 */
class GenerateSchnorrParameters extends Command
{
    protected $signature = 'schnorr:generate-parameters
        {--p-bits=2048 : Bit length of the prime modulus p}
        {--q-bits=256 : Bit length of the subgroup order q}
        {--force : Overwrite existing parameters}';

    protected $description = 'Generate the server-wide Schnorr (p, q, alpha) domain parameters';

    public function handle(
        SchnorrParameterGenerator $generator,
        SchnorrParameterProviderInterface $provider,
    ): int {
        if ($provider->exists() && ! $this->option('force')) {
            $this->error(
                'Schnorr parameters already exist. Regenerating them invalidates every '
                .'existing user\'s Schnorr key pair and signature. Pass --force to proceed anyway.'
            );

            return self::FAILURE;
        }

        $pBits = (int) $this->option('p-bits');
        $qBits = (int) $this->option('q-bits');

        $this->info("Generating Schnorr parameters (p: {$pBits} bits, q: {$qBits} bits). This may take a while...");

        $parameters = $generator->generate($pBits, $qBits);
        $provider->store($parameters);

        $this->info('Schnorr domain parameters generated and stored.');

        return self::SUCCESS;
    }
}
