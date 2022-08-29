<?php

namespace iCoordinator\Console\Command\CustomerSpecific\BDX;

use Adldap\Adldap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetInformationFromAD extends Command
{
    private $ldapConfig = array(
        'base_dn' => 'DC=local,DC=bdx,DC=se',
        'port' => 636,
        'use_ssl' => true
    );
    private $provider;
    private function getMembers($group = false, $inclusive = false)
    {

        $memberlist = array();
        // Search AD
        $group = $this->provider->search()->groups()->find($group);

        if ($group != null) {
            $members = $group->getMembers();

            foreach ($members as $member) {
                $email = $member->getEmail();
                $name = $member->getName();
                if ($email != '') {
                    $memberlist[] = array('name' => $name, 'email' => $email);
                }
            }
        }
        return $memberlist;
    }
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();
        putenv('LDAPTLS_REQCERT=never');
        // Construct new Adldap instance.
        $ad = new Adldap();

        // Create the configuration array.
        /*$config = [
            // Mandatory Configuration Options
            'hosts'            => ['corp-dc1.corp.acme.org', 'corp-dc2.corp.acme.org'],
            'base_dn'          => 'dc=corp,dc=acme,dc=org',
            'username'         => 'admin',
            'password'         => 'password',

            // Optional Configuration Options
            'schema'           => \Adldap\Schemas\ActiveDirectory::class,
            'account_prefix'   => 'ACME-',
            'account_suffix'   => '@acme.org',
            'port'             => 389,
            'follow_referrals' => false,
            'use_ssl'          => false,
            'use_tls'          => false,
            'version'          => 3,
            'timeout'          => 5,

            // Custom LDAP Options
            'custom_options'   => [
                // See: http://php.net/ldap_set_option
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD
            ]
        ];*/
        // Add a connection provider to Adldap.
        $this->ldapConfig['domain_controllers'][] = getenv('BDX_IP');
        $ad->addProvider($this->ldapConfig, 'BDX');
        $getFromAD = true;
        try {
            // If a successful connection is made to your server, the provider will be returned.
            if ($getFromAD) {
                $username = getenv('BDX_USER');
                $password = getenv('BDX_PWD');
                $this->provider = $ad->connect('BDX', $username, $password);
                // Performing a query.
                $memberList = $this->getMembers("iCoordinator Users");
            } else {
                $memberList = array(
                    array('name' => 'Fredrik Lindvall', 'email' => 'fredrik.lindvall@designtech.se'),
                    array('name' => 'Tore Lindbäck', 'email' => 'Tore.Lindback@designtech.se'),
                    array('name' => 'Clark Kent', 'email' => 'clark.kent@designtech.se'),
                    array('name' => 'Lois Lane', 'email' => 'lois.lane@designtech.se'),
                    array('name' => 'Andreas Andersson', 'email' => 'andreas.andersson@designtech.se')
                );
            }
            //print_r($memberList);
            //exit;
            if (count($memberList) > 0) {
                $portalService = $container->get('PortalService');
                $portal = $portalService->getPortal(getenv('BDX_PORTAL')); // BDX portal id
                $portalService->addRemovePortalUsersFromArray($portal, $memberList);
            }
        } catch (BindException $e) {
            // There was an issue binding / connecting to the server.
            echo("Issue binding/connecting to server:" + $e->getMessage());
        }
    }

    protected function configure()
    {
        $this
            ->setName('get-information-from-ad')
            ->setDescription('Pulls information from active directory.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
