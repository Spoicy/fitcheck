# FitCheck

## Installation

**This Plugin requires PHP >=7.2 and Moodle >=3.7 to guarantee functionality.**

To install this Moodle plugin, clone the repository and place it into the "local" folder of your Moodle installation i.e. local/fitcheck. Upon reloading your Moodle website, it will begin the installation of the plugin.

In order for teachers to access the teacher-specific pages, an additional system role has to be created:
* Go to Site administration and go to the "Define roles" page under the "Users" section.
* Click on "Add a new role"
* Select the Teacher (non-editing) Archetype and click Continue.
* Give it an appropriate name such as "Sports Teacher" and under "Context types where this role may assigned", select System and deselect everything else. Then click on create role.
* Once the role is created, you can start assigning the role to teachers.

Any user with the role archetype "Manager" will have additional access to the administrator-only FitCheck pages.