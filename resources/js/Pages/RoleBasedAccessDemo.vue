<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Role-Based Access Control Demo</h3>
          </div>
          
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">User Role Information</div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <h5>Current User Roles:</h5>
                      <ul class="list-group">
                        <li v-for="(name, id) in userTypeNames" :key="id" class="list-group-item">
                          <strong>{{ name }}</strong> (ID: {{ id }})
                        </li>
                      </ul>
                    </div>
                    <div class="col-md-6">
                      <h5>Role Status:</h5>
                      <ul class="list-group">
                        <li class="list-group-item">
                          <i :class="isAdmin ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                          KY Admin
                        </li>
                        <li class="list-group-item">
                          <i :class="isKYUser ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                          KY User
                        </li>
                        <li class="list-group-item">
                          <i :class="isMasterDataUser ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                          Master Data User
                        </li>
                        <li class="list-group-item">
                          <i :class="isPDViewer ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                          PD Viewer
                        </li>
                        <li class="list-group-item">
                          <i :class="isCSNAUser ? 'fas fa-check text-success' : 'fas fa-times text-danger'"></i>
                          CSNA User
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mt-4">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Access Control Examples</div>
                </div>
                <div class="card-body">
                  
                  <!-- Admin Only Section -->
                  <RoleGuard :roles="[1]" show-fallback>
                    <div class="alert alert-success">
                      <h5><i class="fas fa-crown me-2"></i>Admin Only Section</h5>
                      <p>This content is only visible to KY Admin users.</p>
                      <button class="btn btn-success">Admin Action</button>
                    </div>
                  </RoleGuard>

                  <!-- KY User Section -->
                  <RoleGuard :roles="[2]" show-fallback>
                    <div class="alert alert-info">
                      <h5><i class="fas fa-user me-2"></i>KY User Section</h5>
                      <p>This content is only visible to KY User role.</p>
                      <button class="btn btn-info">KY User Action</button>
                    </div>
                  </RoleGuard>

                  <!-- Master Data User Section -->
                  <RoleGuard :roles="[3]" show-fallback>
                    <div class="alert alert-warning">
                      <h5><i class="fas fa-database me-2"></i>Master Data User Section</h5>
                      <p>This content is only visible to Master Data User role.</p>
                      <button class="btn btn-warning">Master Data Action</button>
                    </div>
                  </RoleGuard>

                  <!-- Multiple Roles Section -->
                  <RoleGuard :roles="[1, 2, 3]" show-fallback>
                    <div class="alert alert-primary">
                      <h5><i class="fas fa-users me-2"></i>Multiple Roles Section</h5>
                      <p>This content is visible to KY Admin, KY User, or Master Data User.</p>
                      <button class="btn btn-primary">Multi-Role Action</button>
                    </div>
                  </RoleGuard>

                  <!-- Reports Section -->
                  <RoleGuard :roles="[1, 2, 4]" show-fallback>
                    <div class="alert alert-secondary">
                      <h5><i class="fas fa-chart-bar me-2"></i>Reports Section</h5>
                      <p>This content is visible to KY Admin, KY User, or PD Viewer.</p>
                      <button class="btn btn-secondary">View Reports</button>
                    </div>
                  </RoleGuard>

                  <!-- Conditional Content -->
                  <div class="mt-4">
                    <h5>Conditional Content Examples:</h5>
                    
                    <div v-if="canAccessUserManagement" class="alert alert-success">
                      <i class="fas fa-user-cog me-2"></i>
                      You can access User Management
                    </div>
                    
                    <div v-if="canAccessBudgetHeads" class="alert alert-info">
                      <i class="fas fa-layer-group me-2"></i>
                      You can access Budget Heads
                    </div>
                    
                    <div v-if="canAccessReports" class="alert alert-warning">
                      <i class="fas fa-chart-line me-2"></i>
                      You can access Reports
                    </div>
                    
                    <div v-if="canEditData" class="alert alert-primary">
                      <i class="fas fa-edit me-2"></i>
                      You can edit data
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Header from './Common/Header.vue'
import Sidebar from './Common/Sidebar.vue'
import Footer from './Common/Footer.vue'
import RoleGuard from '../Components/RoleGuard.vue'
import { useRoleAccess } from '../Composables/useRoleAccess'

const page = usePage()
const { 
  hasRole, 
  canAccessUserManagement, 
  canAccessBudgetHeads, 
  canAccessReports, 
  canEditData,
  isAdmin,
  isKYUser,
  isMasterDataUser,
  isPDViewer,
  isCSNAUser
} = useRoleAccess()

const userTypeNames = computed(() => {
  return page.props.auth?.user_type_names || {}
})
</script>

<style scoped>
.alert {
  margin-bottom: 1rem;
}

.list-group-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.list-group-item i {
  margin-right: 0.5rem;
}
</style>
