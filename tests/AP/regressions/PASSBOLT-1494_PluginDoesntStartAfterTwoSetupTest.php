<?php
/**
 * Bug PASSBOLT-1494 - Regression test
 *
 * @copyright (c) 2015-present Bolt Softwares Pvt Ltd
 * @licence GNU Affero General Public License http://www.gnu.org/licenses/agpl-3.0.en.html
 */
class PASSBOLT1494 extends PassboltSetupTestCase
{

	/**
	 * Scenario:  As an AP going through the setup two times, I should be able to login at the end of the second setup.
	 * Given    I create an account as John Doe, and I proceed through the entire setup.
	 * When     I register again with another username, and proceed again through the entire setup
	 * Then     I should be able to see the login form
	 * And      I should be able to login
	 */
	public function testPluginShouldStartAfterTwoSetup() {
		// Register John Doe as a user.
		$john = User::get('john');
		$this->registerUser($john['FirstName'], $john['LastName'], $john['Username']);

		// Go to setup page.
		$this->goToSetup($john['Username']);

		// Wait until I see the setup section domain check.
		$this->waitForSection('domain_check');

		// I should not se any warning.
		$this->assertNotVisible('.plugin-check.warning');

		// Complete registration.
		$this->completeRegistration($john);

		// Change URL in config to hit a different domain.
		$passboltUrl = Config::read('passbolt.url');
		Config::write('passbolt.url', Config::read('passbolt.url_mirror'));

		// Register Curtis Mayfield as a user.
		$curtis = User::get('curtis');
		$this->registerUser($curtis['FirstName'], $curtis['LastName'], $curtis['Username']);

		// Go to setup page.
		$this->goToSetup($curtis['Username'], false);

		// Wait until I see the setup section domain check.
		$this->waitForSection('domain_check');

		// Complete registration.
		$this->completeRegistration($curtis);

		// And I am logged in on the password workspace
		$this->loginAs($curtis);

		// wait for redirection trigger
		sleep(1);
		$this->waitCompletion();

		$this->assertElementContainsText(
			$this->findByCss('.header .user.profile .details .name'),
			$curtis['FirstName'] . ' ' . $curtis['LastName']
		);

		// Database has changed, reset it.
		$this->resetDatabase();

		// Reset the config with the base url.
		Config::write('passbolt.url', $passboltUrl);
	}
}