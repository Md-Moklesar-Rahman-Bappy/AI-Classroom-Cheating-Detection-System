# Video Assets Actions

## Overview

The Video Assets table now supports a full set of actions for valid assets, including viewing, analyzing, editing, and deleting video files. Each action is designed to integrate with the existing workflow for analysis job creation and asset management.

## Table Columns

| Column | Description |
|--------|-------------|
| **SL** | Serial number for row identification |
| **Original** | Original filename of the uploaded video |
| **Stored** | Stored filename (truncated to 20 chars) |
| **Mime** | MIME type of the video |
| **Status** | Validation status (`valid`, `invalid`, or other) with color-coded badge |
| **Linked Jobs** | Count of analysis jobs linked to this video asset |
| **Actions** | View, Analyze, Edit, Delete (visible only for valid assets) |

## Actions

### Valid Asset Actions

Each video asset with `validation_status=valid` shows the following action buttons:

- **View** - Opens the video asset show page for detailed inspection
- **Analyze** - Redirects to the analysis jobs creation page with the video asset selected, allowing the user to create an Analysis Job directly from the selected video asset
- **Edit** - (Authorization required) Opens the edit page to update the video asset's exam session and other metadata
- **Delete** - (Authorization required) Triggers a SweetAlert2 confirmation dialog before soft deleting the video asset

### Delete Action

The delete action performs the following:

1. **SweetAlert2 confirmation** - A confirmation dialog is shown:
   - Title: "Delete video?"
   - Text: "This will soft delete the video asset (recoverable)."
   - Icon: `warning`
   - Show cancel button: true
   - Confirm button color: `#dc3545`
   - Confirm button text: "Delete"

2. **Soft delete** - The video asset record is soft deleted (using Laravel's `SoftDeletes`), keeping the record in the database with a `deleted_at` timestamp while marking it as deleted. The associated video file on disk is also removed.

3. **Audit log** - An audit log entry is created recording the deletion action, including the actor ID, action type (`video_deleted`), and the video asset ID.

### Prevent Unsafe Deletion

Deletion is prevented if the video asset has linked analysis jobs. Attempting to delete a video asset with existing analysis jobs will show an error message: "Cannot delete video with linked jobs."

### Linked Job Count

The "Linked Jobs" column displays the number of analysis jobs associated with each video asset via the `analysisJobs()` relationship. This count is fetched eagerly using the `linkedJobCount` attribute on the VideoAsset model.

## Authorization

- **View**: Available to all authenticated users
- **Analyze**: Available to all authenticated users with the `create` permission on analysis jobs
- **Edit**: Requires the `update` permission on the video asset (typically system admins and exam admins)
- **Delete**: Requires the `delete` permission on the video asset (typically system admins only)

Role-based access control is enforced through Laravel's `@can` directives in the blade template. Users with the `auditor` role cannot delete video assets.

## Workflow Flow

1. User navigates to the Video Assets page
2. See the table with SL, Original, Stored, Mime, Status, Linked Jobs, and Actions columns
3. For valid assets, click action buttons as needed:
   - **View** → video asset show page
   - **Analyze** → analysis jobs creation page
   - **Edit** → video asset edit page
   - **Delete** → SweetAlert2 confirmation → soft delete + audit log
4. Linked job count updates dynamically as analysis jobs are created or deleted