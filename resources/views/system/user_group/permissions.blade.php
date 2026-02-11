<!-- Permissions Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="permissionsForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionsModalLabel">Menu Permissions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="perm_group_id" name="perm_group_id">
                    <div id="permissions-content">
                        <!-- Permissions will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>
