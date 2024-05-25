<?php

namespace Slides\Saml2\Commands;

use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;
use Slides\Saml2\Helpers\ConsoleHelper;
use Slides\Saml2\Repositories\IdentityProviderRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends \Illuminate\Console\Command
{
    use RendersTenants, ValidatesInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saml2:idp-update {id}
                            { --key : Key/name of the Identity Provider }
                            { --entityId= : IdP Issuer URL }
                            { --loginUrl= : IdP Sign on URL }
                            { --logoutUrl= : IdP Logout URL }
                            { --relayStateUrl= : Redirection URL after successful login }
                            { --nameIdFormat= : Name ID Format ("persistent" by default) }
                            { --x509cert= : x509 certificate (base64) }
                            { --metadata= : A custom metadata }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update an existing Identity Provider';

    /**
     * @var IdentityProviderRepository
     */
    protected IdentityProviderRepository $repository;

    /**
     * DeleteTenant constructor.
     *
     * @param IdentityProviderRepository $repository
     */
    public function __construct(IdentityProviderRepository $repository)
    {
        $this->repository = $repository;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $tenant = $this->repository->findById($this->argument('id'));

        if (! $tenant) {
            $this->error(sprintf('Identity Provider %s not found.', $this->argument('id')));
            return 1;
        }

        $tenant->update(array_filter([
            'key' => $this->option('key'),
            'idp_entity_id' => $this->option('entityId'),
            'idp_login_url' => $this->option('loginUrl'),
            'idp_logout_url' => $this->option('logoutUrl'),
            'idp_x509_cert' => $this->option('x509cert'),
            'relay_state_url' => $this->option('relayStateUrl'),
            'name_id_format' => $this->option('nameIdFormat'),
            'metadata' => ConsoleHelper::stringToArray($this->option('metadata'))
        ]));

        if (!$tenant->save()) {
            $this->error('Identity Provider cannot be saved.');
            return;
        }

        $this->info("The tenant #$tenant->id ($tenant->uuid) was successfully updated.");

        $this->renderTenantCredentials($tenant);

        $this->output->newLine();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (! $input->getOption('entityId')) {
            $input->setOption('entityId', $this->ask('Entity ID (fx. https://sts.windows.net/65b9e948-757b-4431-b140-62a2f8a3fdeb/) (optional)'));
        }

        if (! $input->getOption('loginUrl')) {
            $input->setOption('loginUrl', $this->ask('Login URL (fx. https://login.microsoftonline.com/65b9e948-757b-4431-b140-62a2f8a3fdeb/saml2)'));
        }

        if (! $input->getOption('logoutUrl')) {
            $input->setOption('logoutUrl', $this->ask('Logout URL'));
        }

        if (! $input->getOption('nameIdFormat')) {
            $input->setOption('nameIdFormat', $this->choice('Name ID Format', $this->nameIdFormatValues(), 'persistent'));
        }

        if (! $input->getOption('relayStateUrl')) {
            $input->setOption('relayStateUrl', $this->ask('Post-login redirect URL (optional)'));
        }

        if (! $input->getOption('key')) {
            $input->setOption('key', $this->ask('Key/name of the identity provider (optional)'));
        }

        if (! $input->getOption('metadata')) {
            $input->setOption('metadata', $this->ask('Custom metadata (in format "field:value,anotherfield:value") (optional)'));
        }
    }

    /**
     * Validation rules for the user input.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'id' => ['required', 'int', new Exists(config('saml2.idpModel'))],
            'key' => ['string', new Unique(config('saml2.idpModel'), 'key')],
            'entityId' => 'string',
            'loginUrl' => 'string|url',
            'logoutUrl' => 'string|url',
            'x509cert' => 'string',
            'relayStateUrl' => 'string|url',
            'metadata' => 'string',
            'nameIdFormat' => ['string', new In($this->nameIdFormatValues())]
        ];
    }

    /**
     * Get the values for the name ID format rule.
     *
     * @return string[]
     */
    protected function nameIdFormatValues(): array
    {
        return [
            'persistent',
            'transient',
            'emailAddress',
            'unspecified',
            'X509SubjectName',
            'WindowsDomainQualifiedName',
            'kerberos',
            'entity'
        ];
    }
}
