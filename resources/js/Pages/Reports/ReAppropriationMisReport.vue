<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container py-4">
          <div class="page-inner allinsideform">
           
          
            <div class="page-header">
              <h3 class="fw-bold mb-3">MIS Reports & Dashboards</h3>
              <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                  <a href="login.html">
                    <i class="icon-home"></i>
                  </a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">Re-appropriation MIS Report</a>
                </li>
               
              </ul>
            </div>

          
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                
                  <div class="card-header">
                    <div class="card-title">Re-appropriation MIS Report</div>
                  </div>


                  <div class="card-body">
                    <div class="row">
                      
                      
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="email2">F.Y</label>
                          <select class="form-select" id="financialYear" disabled>
                         
                            <option value="2024-2025">2024–2025</option>
                         
                            <!-- Add more years as needed -->
                          </select>
                         
                           
                        </div>
                       
                       
                      </div>

                      


                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="email2">Program Division</label>
                          <select class="form-select" id="financialYear">
                            <option selected disabled>Select Program</option>
                           </select>
                         </div>
                       </div>


                       <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="email2">RO Date</label>
                          <input type="date" class="form-control" >
                         </div>
                       </div>


                    </div>

                    <div class="table-responsive mt-3">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr>
                            <th>S.No</th>
                            <th>Division</th>
                            <th>Re-appropriation From Head</th>
                            <th>Re-appropriation to Head</th>
                            <th>Re-appropriation to Entity Type</th>
                            <th>Re-appropriation to Entity Name</th>
                            <th>Amount (Rs. in Lakh)</th>
                            <th>RO Date</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <th scope="row" rowspan="3">1</th>
                            <td rowspan="3">Division 1</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>
                          <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>
                          <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>

                          <tr>
                            <td></td>
                            <td>Division 1 Total</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>


                          <tr>
                            <th scope="row" rowspan="3">2</th>
                            <td rowspan="3">Division 2</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>
                          <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>
                          <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>

                          <tr>
                            <td></td>
                            <td>Division 2 Total</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                          </tr>


                        </tbody>
                      </table>
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
<style scoped>
.wrapper {
  background-color: #f9fafb;
  min-height: 100vh;
}
.page-header h3 {
  letter-spacing: 0.05em;
}
.table thead.table-primary {
  background-color: #cfe2ff;
  border-color: #b6d4fe;
}
.table-hover tbody tr:hover {
  background-color: #f1f5ff;
}
.form-control-plaintext {
  background-color: transparent;
  border: none;
  padding-left: 0;
  font-size: 1rem;
}
.form-select {
  border-radius: 0.375rem;
  border: 1px solid #ced4da;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgb(13 110 253 / 0.25);
  outline: none;
}
.editable-input {
  transition: background-color 0.3s ease, border-color 0.3s ease;
}
.editable-input:focus {
  border-color: #0d6efd;
  background-color: #e7f1ff;
  outline: none;
}
.sticky-header {
  position: sticky;
  top: 0;
  z-index: 2;
}

/* Buttons */
.btn {
  min-width: 110px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  border-radius: 0.375rem;
  transition: background-color 0.25s ease, box-shadow 0.25s ease;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #0d6efd;
  border: none;
  color: white;
}
.btn-primary:hover:not(:disabled) {
  background-color: #0b5ed7;
  box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
}

.btn-outline-primary {
  border: 2px solid #0d6efd;
  color: #0d6efd;
  background: none;
}
.btn-outline-primary:hover:not(:disabled) {
  background-color: #0d6efd;
  color: white;
  box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
}

.btn-secondary {
  background-color: #6c757d;
  border: none;
  color: white;
}
.btn-secondary:hover:not(:disabled) {
  background-color: #5c636a;
  box-shadow: 0 0 8px rgba(108, 117, 125, 0.5);
}

/* Responsive tweaks */
@media (max-width: 575px) {
  .d-flex.justify-content-end.flex-wrap {
    justify-content: center !important;
  }
}
</style>

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, onMounted, watch } from 'vue'

export default {
  name: 'BudgetPhaseReport',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    const financialYear = ref('2024-2025')
    const filteredBudgetHeads = ref([])
    const isLoading = ref(false)

    const fetchBudgetSummary = async () => {
      if (!financialYear.value) return
      isLoading.value = true

      try {
        const res = await fetch(`/api/budget-phase-summary?year=${financialYear.value}`)
        const data = await res.json()
        filteredBudgetHeads.value = data
      } catch (error) {
        console.error('Error fetching budget summary:', error)
      } finally {
        isLoading.value = false
      }
    }

    onMounted(fetchBudgetSummary)
    watch(financialYear, fetchBudgetSummary)

    return {
      financialYear,
      filteredBudgetHeads,
      isLoading
    }
  }
}
</script>
