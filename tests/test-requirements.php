<?php

use PHPUnit\Framework\TestCase;
use YourPluginNamespace\RequirementsChecker;

class RequirementsCheckerTest extends TestCase {

    /**
     * Test with requirements met
     *
     * @access public
     * @since  1.0.0
     *
     * @return void
     */
    public function testMetWithMetRequirements() {
        // Create a mock for the Loader and Install classes (replace with actual mocks if needed).
        $loaderMock = $this->getMockBuilder('Intercessor\Loader')->getMock();
        $installMock = $this->getMockBuilder('Intercessor\Install')->getMock();

        // Create an instance of RequirementsChecker with mocked dependencies.
        $requirementsChecker = new RequirementsChecker($loaderMock, $installMock);

        // Define mocked requirements that are met.
        $requirements = [
            'wp' => [
                'minimum' => '5.0',
                'name'    => 'WordPress',
                'exists'  => true,
                'current' => '5.5.1',  // Met requirement.
                'checked' => true,
                'met'     => true,  // Met requirement.
            ],
            'php' => [
                'minimum' => '7.0.0',
                'name'    => 'PHP',
                'exists'  => true,
                'current' => '7.4.1',  // Met requirement.
                'checked' => true,
                'met'     => true,  // Met requirement.
            ],
        ];

        // Set the requirements property to the mocked requirements.
        $requirementsChecker->setRequirements($requirements);

        // Assert that the met() method returns true when requirements are met.
        $this->assertTrue($requirementsChecker->met());
    }

    /**
     * Test with requirements not met
     *
     * @access public
     * @since  1.0.0
     *
     * @return void
     */
    public function testMetWithUnmetRequirements() {
        // Create a mock for the Loader and Install classes (replace with actual mocks if needed).
        $loaderMock = $this->getMockBuilder('Intercessor\Loader')->getMock();
        $installMock = $this->getMockBuilder('Intercessor\Install')->getMock();

        // Create an instance of RequirementsChecker with mocked dependencies.
        $requirementsChecker = new RequirementsChecker($loaderMock, $installMock);

        // Define mocked requirements that are not met.
        $requirements = [
            'wp' => [
                'minimum' => '5.0',
                'name'    => 'WordPress',
                'exists'  => true,
                'current' => '4.9.9',  // Unmet requirement.
                'checked' => true,
                'met'     => false,  // Unmet requirement.
            ],
            'php' => [
                'minimum' => '7.0.0',
                'name'    => 'PHP',
                'exists'  => true,
                'current' => '5.6.0',  // Unmet requirement.
                'checked' => true,
                'met'     => false,  // Unmet requirement.
            ],
        ];

        // Set the requirements property to the mocked requirements.
        $requirementsChecker->setRequirements($requirements);

        // Assert that the met() method returns false when requirements are not met.
        $this->assertFalse($requirementsChecker->met());
    }
}
