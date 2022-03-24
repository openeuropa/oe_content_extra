# OpenEuropa Content Extra Project

The module enhances the 'Project' content type from `oe_content_project`.

It also introduces a new organisation type, "Stakeholder with budget", to be referenced from project fields.

It adds the following fields:
- Achievements and milestones.
- Gallery.
- Impacts.
- Lead contributors: References "Stakeholder with budget" organisations.
- Objective.

It modifies the following field:
- Participants: References "Stakeholder with budget" organisations instead of "Stakeholder".

It also overrides the form display and view display, to add the new fields.
