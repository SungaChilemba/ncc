<?php

namespace App\Support;

/**
 * Code-owned catalog of permissions and the built-in roles that ship with the
 * application.
 *
 * Permissions are deliberately defined here rather than created freely from
 * the CMS: a permission only enforces anything when application code checks
 * that exact key. Administrators compose roles from this catalog; a brand-new
 * capability still requires a code release.
 */
class AccessCatalog
{
    /**
     * @return array<string, array{group: string, label: string, description: string, dangerous: bool}>
     */
    public static function permissions(): array
    {
        return [
            'cms.dashboard.view' => [
                'group' => 'cms',
                'label' => 'View CMS dashboard',
                'description' => 'Open the website content dashboard and see publishing summaries.',
                'dangerous' => false,
            ],
            'cms.content.view' => [
                'group' => 'cms',
                'label' => 'View website content',
                'description' => 'Browse and preview content in every CMS section.',
                'dangerous' => false,
            ],
            'cms.content.create' => [
                'group' => 'cms',
                'label' => 'Create website content',
                'description' => 'Add new content items to CMS sections.',
                'dangerous' => false,
            ],
            'cms.content.edit' => [
                'group' => 'cms',
                'label' => 'Edit website content',
                'description' => 'Change existing content items and restore revisions.',
                'dangerous' => false,
            ],
            'cms.content.delete' => [
                'group' => 'cms',
                'label' => 'Delete website content',
                'description' => 'Permanently remove content items.',
                'dangerous' => true,
            ],
            'cms.content.publish' => [
                'group' => 'cms',
                'label' => 'Publish website content',
                'description' => 'Publish, unpublish, and bulk-change content visibility.',
                'dangerous' => true,
            ],
            'cms.content.reorder' => [
                'group' => 'cms',
                'label' => 'Reorder website content',
                'description' => 'Change the display order of content within a section.',
                'dangerous' => false,
            ],
            'cms.media.manage' => [
                'group' => 'cms',
                'label' => 'Manage media library',
                'description' => 'Upload, edit, and delete website media assets.',
                'dangerous' => false,
            ],
            'cms.newsletter.view' => [
                'group' => 'cms',
                'label' => 'View newsletter subscribers',
                'description' => 'See the newsletter subscriber list.',
                'dangerous' => false,
            ],
            'cms.newsletter.export' => [
                'group' => 'cms',
                'label' => 'Export newsletter subscribers',
                'description' => 'Download subscriber email addresses.',
                'dangerous' => true,
            ],
            'cms.newsletter.delete' => [
                'group' => 'cms',
                'label' => 'Delete newsletter subscribers',
                'description' => 'Remove subscribers from the newsletter list.',
                'dangerous' => true,
            ],
            'cms.enquiries.manage' => [
                'group' => 'cms',
                'label' => 'Manage advertising enquiries',
                'description' => 'Review, approve, reject, and renew advertising enquiries.',
                'dangerous' => false,
            ],

            'cases.view' => [
                'group' => 'cases',
                'label' => 'View case queue',
                'description' => 'See cases allowed by the role\'s case scope.',
                'dangerous' => false,
            ],
            'cases.identity.view' => [
                'group' => 'cases',
                'label' => 'View child and reporter identity',
                'description' => 'See names, contact details, village, and the full narrative.',
                'dangerous' => true,
            ],
            'cases.notes.add' => [
                'group' => 'cases',
                'label' => 'Add internal case notes',
                'description' => 'Write encrypted internal notes on a case.',
                'dangerous' => false,
            ],
            'cases.assign' => [
                'group' => 'cases',
                'label' => 'Assign cases',
                'description' => 'Assign or unassign cases to case staff.',
                'dangerous' => true,
            ],
            'cases.status.change' => [
                'group' => 'cases',
                'label' => 'Change case status',
                'description' => 'Move a case through the allowed status graph.',
                'dangerous' => false,
            ],
            'cases.transition.triage' => [
                'group' => 'cases',
                'label' => 'Move to assessment',
                'description' => 'Triage a newly received case.',
                'dangerous' => false,
            ],
            'cases.transition.investigation' => [
                'group' => 'cases',
                'label' => 'Start an investigation',
                'description' => 'Move a case into investigation.',
                'dangerous' => false,
            ],
            'cases.transition.referred' => [
                'group' => 'cases',
                'label' => 'Refer for support',
                'description' => 'Refer a case to a support service.',
                'dangerous' => false,
            ],
            'cases.transition.resolved' => [
                'group' => 'cases',
                'label' => 'Mark resolved',
                'description' => 'Record that a case has been resolved.',
                'dangerous' => false,
            ],
            'cases.transition.closed' => [
                'group' => 'cases',
                'label' => 'Close a case',
                'description' => 'Close a case and start its retention clock.',
                'dangerous' => true,
            ],
            'cases.transition.duplicate' => [
                'group' => 'cases',
                'label' => 'Mark duplicate',
                'description' => 'Close a case as a duplicate report.',
                'dangerous' => true,
            ],
            'cases.transition.withdrawn' => [
                'group' => 'cases',
                'label' => 'Mark withdrawn',
                'description' => 'Record that the reporter withdrew the case.',
                'dangerous' => true,
            ],
            'cases.evidence.upload' => [
                'group' => 'cases',
                'label' => 'Upload case evidence',
                'description' => 'Attach files to a case.',
                'dangerous' => false,
            ],
            'cases.evidence.download' => [
                'group' => 'cases',
                'label' => 'Download case evidence',
                'description' => 'Open or download evidence files.',
                'dangerous' => true,
            ],
            'cases.audit.view' => [
                'group' => 'cases',
                'label' => 'View case access trail',
                'description' => 'See who opened, changed, or downloaded a case.',
                'dangerous' => false,
            ],

            'admin.users.view' => [
                'group' => 'admin',
                'label' => 'View users',
                'description' => 'See admin accounts and their assigned roles.',
                'dangerous' => false,
            ],
            'admin.users.manage' => [
                'group' => 'admin',
                'label' => 'Manage users',
                'description' => 'Create users, assign roles, deactivate accounts, and reset two-factor authentication.',
                'dangerous' => true,
            ],
            'admin.roles.manage' => [
                'group' => 'admin',
                'label' => 'Manage roles and permissions',
                'description' => 'Create roles and choose which permissions each role receives.',
                'dangerous' => true,
            ],
            'admin.audit.view' => [
                'group' => 'admin',
                'label' => 'View access-control audit log',
                'description' => 'Review every user, role, and permission change.',
                'dangerous' => false,
            ],
        ];
    }

    /**
     * @return array<string, array{name: string, description: string, category: string, case_scope: string, can_be_assigned: bool, requires_totp: bool, is_system: bool, permissions: list<string>}>
     */
    public static function roles(): array
    {
        return [
            'system_admin' => [
                'name' => 'System Administrator',
                'description' => 'Manages users, roles, and website content. No case data access unless a case role is also assigned.',
                'category' => 'admin',
                'case_scope' => 'none',
                'can_be_assigned' => false,
                'requires_totp' => false,
                'is_system' => true,
                'permissions' => [
                    ...self::cmsPermissionKeys(),
                    'admin.users.view',
                    'admin.users.manage',
                    'admin.roles.manage',
                    'admin.audit.view',
                ],
            ],
            'cms_admin' => [
                'name' => 'CMS Administrator',
                'description' => 'Full control of website content, media, newsletters, and enquiries.',
                'category' => 'cms',
                'case_scope' => 'none',
                'can_be_assigned' => false,
                'requires_totp' => false,
                'is_system' => true,
                'permissions' => self::cmsPermissionKeys(),
            ],
            'cms_editor' => [
                'name' => 'CMS Editor',
                'description' => 'Manages website sections and media. Cannot access case data.',
                'category' => 'cms',
                'case_scope' => 'none',
                'can_be_assigned' => false,
                'requires_totp' => false,
                'is_system' => true,
                'permissions' => self::cmsPermissionKeys(),
            ],
            'case_intake' => [
                'name' => 'Intake Officer',
                'description' => 'Sees all cases, assigns work, and triages new reports.',
                'category' => 'case',
                'case_scope' => 'all',
                'can_be_assigned' => false,
                'requires_totp' => true,
                'is_system' => true,
                'permissions' => [
                    'cases.view',
                    'cases.identity.view',
                    'cases.notes.add',
                    'cases.assign',
                    'cases.status.change',
                    'cases.transition.triage',
                    'cases.transition.duplicate',
                    'cases.transition.withdrawn',
                    'cases.evidence.upload',
                    'cases.evidence.download',
                ],
            ],
            'case_officer' => [
                'name' => 'Case Officer',
                'description' => 'Sees and works only on cases assigned to them.',
                'category' => 'case',
                'case_scope' => 'assigned',
                'can_be_assigned' => true,
                'requires_totp' => true,
                'is_system' => true,
                'permissions' => [
                    'cases.view',
                    'cases.identity.view',
                    'cases.notes.add',
                    'cases.status.change',
                    ...self::caseTransitionKeys(),
                    'cases.evidence.upload',
                    'cases.evidence.download',
                ],
            ],
            'case_manager' => [
                'name' => 'Case Manager',
                'description' => 'Oversees every case, assigns staff, and reviews the access trail.',
                'category' => 'case',
                'case_scope' => 'all',
                'can_be_assigned' => true,
                'requires_totp' => true,
                'is_system' => true,
                'permissions' => [
                    'cases.view',
                    'cases.identity.view',
                    'cases.notes.add',
                    'cases.assign',
                    'cases.status.change',
                    ...self::caseTransitionKeys(),
                    'cases.evidence.upload',
                    'cases.evidence.download',
                    'cases.audit.view',
                ],
            ],
            'case_auditor' => [
                'name' => 'Auditor',
                'description' => 'Reviews case metadata and access trails without seeing identity, notes, or evidence.',
                'category' => 'case',
                'case_scope' => 'all',
                'can_be_assigned' => false,
                'requires_totp' => true,
                'is_system' => true,
                'permissions' => [
                    'cases.view',
                    'cases.audit.view',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function cmsPermissionKeys(): array
    {
        return array_values(array_filter(
            array_keys(self::permissions()),
            fn (string $key) => str_starts_with($key, 'cms.'),
        ));
    }

    /**
     * @return list<string>
     */
    public static function caseTransitionKeys(): array
    {
        return [
            'cases.transition.triage',
            'cases.transition.investigation',
            'cases.transition.referred',
            'cases.transition.resolved',
            'cases.transition.closed',
            'cases.transition.duplicate',
            'cases.transition.withdrawn',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function allPermissionKeys(): array
    {
        $keys = [];

        foreach (self::permissions() as $key => $meta) {
            $keys[$key] = $meta['label'];
        }

        return $keys;
    }
}
