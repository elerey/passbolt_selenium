<?php
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Feature :  As an administrator I can edit groups
 *
 * Scenarios :
 *  - As an administrator I can edit a group using the right click contextual menu
 *  - As an administrator I can edit the group name
 *  - As an administrator I can edit a group from the sidebar
 *  - As an administrator I cannot add people to a group I am not a group manager of
 *  - As an administrator I can't change a group with a name for a name already used by another group
 *
 * @copyright (c) 2017-present Passbolt SARL
 * @licence GNU Affero General Public License http://www.gnu.org/licenses/agpl-3.0.en.html
 */
class ADGroupEditTest extends PassboltTestCase {

	/**
	 * Scenario: As an administrator I can edit a group using the right click contextual menu
	 *
	 * Given	I am logged in as an administrator and I am on the users workspace
	 * When 	I click on the contextual menu button of a group on the right
	 * Then 	I should see the group contextual menu
	 * And  	I should see the “Edit group” option
	 * When		I click on “Edit group”
	 * Then		I should see the Edit group dialog
	 */
	public function testEditGroupRightClick() {
		// Given I am logged in as an administrator
		$user = User::get('admin');
		$this->setClientConfig($user);
		$this->loginAs($user);
		$this->gotoWorkspace('user');

		// When I click on the contextual menu button of a group on the right
		$groupId = Uuid::get('group.id.ergonom');
		$this->click("#group_$groupId .right-cell a");

		// Then I should see the group contextual menu
		$this->assertVisible('#js_contextual_menu');
		$this->assertVisible('js_group_browser_menu_edit');

		// When I click on “Edit group”
		$this->click("#js_contextual_menu #js_group_browser_menu_edit a");

		// Then I should see the Edit group dialog
		$this->waitUntilISee('.edit-group-dialog');
	}

	/**
	 * Scenario: As an administrator I can edit the group name
	 *
	 * Given	I am logged in as administrator
	 * And		I am editing a group
	 * When		I observe the content of the edit group dialog
	 * Then		I should see a “group name” field containing the current group name.
	 * When		I modify the group name
	 * And		I click on “save”
	 * Then		I should see that the dialog disappears
	 * And		I should see a confirmation message saying that the group has been edited
	 * And		I should see that the group name has been changed in the groups list
	 */
	public function testEditGroupName() {
		$this->resetDatabaseWhenComplete();

		// Given I am logged in as an administrator
		$user = User::get('admin');
		$this->setClientConfig($user);
		$this->loginAs($user);
		$group = Group::get(['id' => Uuid::get('group.id.ergonom')]);
		$this->gotoEditGroup($group['id']);

		// When	I observe the content of the edit group dialog
		// Then	I should see a “group name” field containing the current group name.
		$this->assertInputValue('js_field_name', $group['name']);

		// When	I modify the group name
		$groupNameUpdate = $group['name'] . ' UPDATED';
		$this->inputText('js_field_name', $groupNameUpdate);

		// And I click on “save”
		$this->click('.edit-group-dialog a.button.primary');

		// Then	I should see that the dialog disappears
		$this->waitUntilIDontSee('.edit-group-dialog');

		// And I should see a confirmation message saying that the group has been edited
		$this->assertNotification('app_groups_edit_success');

		// And I should see that the group name has been changed in the groups list
		$this->waitUntilISee('js_wsp_users_groups_list', '/' . $groupNameUpdate . '/');
	}

	/**
	 * Scenario: As an administrator I can edit a group from the sidebar
	 *
	 * Given	I am logged in as administrator
	 * And		I am on the user workspace
	 * And		I should see a “edit” button next to the Information section
	 * When		I press the “Edit” button
	 * Then 	I should see the Edit group dialog
	 */
	public function testEditGroupFromSidebar() {
		// Given I am logged in as an administrator
		$user = User::get('admin');
		$this->setClientConfig($user);
		$this->loginAs($user);
		$this->gotoWorkspace('user');

		// When I click a group name
		$group = Group::get(['id' => Uuid::get('group.id.ergonom')]);
		$this->clickGroup($group['id']);

		// Then I should see a “edit” button next to the Information section
		$editButtonSelector = '#js_group_details #js_group_details_members #js_edit_members_button';
		$this->waitUntilISee($editButtonSelector);

		// When I press the “Edit” button
		$this->click($editButtonSelector);

		// Then I should see the Edit group dialog
		$this->waitUntilISee('.edit-group-dialog');
	}

	/**
	 * Scenario: As an administrator I cannot add people to a group I am not a group manager of.
	 *
	 * Given	I am logged in as administrator
	 * And		I am editing a group that I am not the group manager of
	 * When		I observe the content of the edit group dialog
	 * Then     I should not see a Add people section
	 *  And     I should see a warning message saying that "Only the group manager can add new people to a group."
	 */
	public function testEditGroupAsNotGroupManager() {
		// Given I am logged in as an administrator
		$user = User::get('admin');
		$this->setClientConfig($user);
		$this->loginAs($user);
		$group = Group::get(['id' => Uuid::get('group.id.accounting')]);
		$this->gotoEditGroup($group['id']);

		// And I shouldn't see the Add people iframe.
		$this->assertNotVisible('#js_group_members_add #passbolt-iframe-group-edit');

		// And I see a warning message saying that only the group manager can add new people to a group.
		$this->assertElementContainsText('#js_group_members .message.warning', 'Only the group manager can add new people to a group.');
	}

	/**
	 * Scenario:    As an administrator I can't change a group with a name for a name already used by another group.
	 * Given that   I am logged in as an administrator
	 * And          I am on the users workspace
	 * When         I edit the "accounting" group
	 *  And         I fill 'Board' for name
	 *  And         I click on submit
	 * Then         I should see a notification saying that the group couldn't be updated
	 *  And         I should see an error message under the name field
	 *  And         I should see that this error message says that the group name is already in use
	 */
	public function testEditGroupNameValidation() {
		// Given I am logged in as an administrator
		$user = User::get('admin');
		$this->setClientConfig($user);
		$this->loginAs($user);
		$group = Group::get(['id' => Uuid::get('group.id.accounting')]);

		// When I edit a the group "accounting".
		$this->gotoEditGroup($group['id']);

		// When I enter board as a name (the name is already used).
		$this->inputText('js_field_name', $group = Group::get(['id' => Uuid::get('group.id.board')])['name']);

		// And I click on “save”
		$this->click('.edit-group-dialog a.button.primary');

		// Then I should see a success notification message saying the group couldn't be created
		$this->assertNotification('app_groups_add_error');

		// Then I see an error message saying that the name contain invalid characters
		$this->waitUntilISee('#js_field_name_feedback.error.message');

		$this->assertElementContainsText(
			$this->find('js_field_name_feedback'), 'The group name provided is already used by another group'
		);

	}
}