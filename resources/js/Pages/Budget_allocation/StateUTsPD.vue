<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Master Data</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="login.html"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">States/UTs - PD/Component - SLS IDs Mapping</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <!-- Accordion Container -->
              <div class="accordion" id="stateUTsAccordion">
                
                <!-- Section 1: State-wise list of PD/Component and SLS -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section1 }" type="button" @click="toggleAccordion('section1')" aria-expanded="true" aria-controls="collapseOne">
                      <i class="fas fa-map-marker-alt me-2"></i>
                      State-wise list of PD/Component and SLS
                    </button>
                  </h2>
                  <div id="collapseOne" class="accordion-collapse" :class="{ 'show': accordionStates.section1 }" aria-labelledby="headingOne" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section1">
                      <div class="card">
                        <div class="card-body">
                          <!-- First Table: Static with Component Dropdown -->
                          <div class="table-responsive mt-1" style="width: 50%;">
                            <table class="table table-bordered table-head-bg-primary">
                              <thead>
                                <tr>
                                  <th>Component</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr>
                                  <td>
                                    <select v-model="selectedComponent" class="form-select">
                                      <option value="">--- Select ---</option>
                                      <option value="PD">PD Component</option>
                                      <option value="SL">SLS ID</option>
                                    </select>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>

                          <!-- Second Table: Conditionally rendered -->
                          <div class="table-responsive mt-1" v-if="selectedComponent">
                            <table class="table table-bordered table-head-bg-primary">
                              <thead>
                                <tr>
                                  <th v-if="selectedComponent === 'SL'">PD</th>
                                  <th v-if="selectedComponent === 'SL'">State/UT</th>
                                  <th v-if="selectedComponent === 'PD'">PD/Component</th>
                                  <th v-if="selectedComponent === 'SL'">SLS ID</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(row, index) in formRows" :key="index">
                                  <td v-if="selectedComponent === 'SL'">
                                   <select v-model="row.slsPD" class="form-select">
                                      <option value="">--- Select PD ---</option>
                                      <option
                                        v-for="(item, index) in savedData.filter(i => i.component === 'PD')"
                                        :key="item.id"
                                        :value="item.name"
                                      >
                                        {{ item.name }}
                                      </option>

                                    </select>

                                  </td>

                                  <td v-if="selectedComponent === 'SL'">
                                   <select v-model="row.state" class="form-select">

                                      <option value="">--- Select State ---</option>
                                      <option v-for="state in states" :key="state.id" :value="state.id">
                                        {{ state.name }}
                                      </option>
                                    </select>
                                  </td>

                                  <td v-if="selectedComponent === 'PD'">
                                    <input v-model="row.pdComponent" type="text" class="form-control" placeholder="Enter PD/Component" />
                                  </td>

                                  <td v-if="selectedComponent === 'SL'">
                                    <input v-model="row.slsId" type="text" class="form-control" placeholder="Enter SLS ID" />
                                  </td>
                                </tr>
                              </tbody>
                            </table>

                            <!-- Add Row -->
                            <div class="col-md-12 col-lg-12">
                              <button class="btn btn-primary me-1 mb-4" @click="addRow">+ Add New</button>
                            </div>
                          </div>

                          <!-- Buttons -->
                          <div class="col-12 d-flex justify-content-center">
                            <button class="btn btn-success me-1" @click="submit">Submit</button>
                            <button class="btn btn-danger me-1">Reset</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 2: PD Component List -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section2 }" type="button" @click="toggleAccordion('section2')" aria-expanded="false" aria-controls="collapseTwo">
                      <i class="fas fa-list-alt me-2"></i>
                      PD Component List
                    </button>
                  </h2>
                  <div id="collapseTwo" class="accordion-collapse" :class="{ 'show': accordionStates.section2 }" aria-labelledby="headingTwo" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section2">
                      <div class="card">
                        <div class="card-body">
                          <div class="bg-primary text-white px-3 py-2 rounded mb-3">
                            <h5 class="mb-0">PD Component List</h5>
                          </div>
                          
                          <!-- PD Table -->
                          <div class="table-responsive mt-4">
                            <DataTable :columns="pdColumns" :data="pdData" class="table table-bordered table-striped"
                              :options="{ responsive: true, pageLength: 5, lengthChange: false }">
                              <template #thead>
                                <thead>
                                  <tr>
                                    <th>S. No.</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                  </tr>
                                </thead>
                              </template>

                              <template #row="{ row, rowIndex }">
                                <tr>
                                   <td>{{ row.serial }}</td>
                                  <td>{{ row.component }}</td>
                                  <td>{{ row.name }}</td>
                                  <td>
                                    <a class="me-2" @click="deleteRow(row.id)">
                                      <i class="fas fa-trash"></i>
                                    </a>
                                  </td>
                                </tr>
                              </template>
                            </DataTable>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 3: SL Component List -->
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button" :class="{ 'collapsed': !accordionStates.section3 }" type="button" @click="toggleAccordion('section3')" aria-expanded="false" aria-controls="collapseThree">
                      <i class="fas fa-layer-group me-2"></i>
                      SL Component List
                    </button>
                  </h2>
                  <div id="collapseThree" class="accordion-collapse" :class="{ 'show': accordionStates.section3 }" aria-labelledby="headingThree" data-bs-parent="#stateUTsAccordion">
                    <div class="accordion-body" v-show="accordionStates.section3">
                      <div class="card">
                        <div class="card-body">
                          <div class="bg-primary text-white px-3 py-2 rounded mb-3">
                            <h5 class="mb-0">SL Component List</h5>
                          </div>
                          
                          <!-- SL Table -->
                          <div class="table-responsive mt-4">
                            <DataTable :columns="slColumns" :data="slData" class="table table-bordered table-striped"
                              :options="{ responsive: true, pageLength: 5, lengthChange: false }">
                              <template #thead>
                                <thead>
                                  <tr>
                                    <th>S. No.</th>
                                    <th>Type</th>
                                    <th>State</th>
                                    <th>PD</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                  </tr>
                                </thead>
                              </template>

                              <template #row="{ row, rowIndex }">
                                <tr>
                                   <td>{{ row.serial }}</td>
                                  <td>{{ row.component }}</td>
                          <td>{{ row.state?.name || '-' }}</td>

                                  <td>{{ row.name }}</td>
                                  <td>
                                    <a class="me-2" @click="deleteRow(row.id)">
                                      <i class="fas fa-trash"></i>
                                    </a>
                                  </td>
                                </tr>
                              </template>
                            </DataTable>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div> <!-- End Accordion Container -->
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>
<script setup>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, watch, onMounted,computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net-bs5'
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import 'datatables.net-bs5/css/dataTables.bootstrap5.css'

DataTable.use(DataTablesCore)
const pdColumns = [
  { title: 'S. No.', data: 'serial',width: '1%' },

  { title: 'Type', data: 'component',width: '3%' },
  { title: 'Name', data: 'name' },
  
]

const slColumns = [

  { title: 'S. No.', data: 'serial',width: '1%' },
  { title: 'Type', data: 'component',width: '3%' },
  { title: 'State', data: 'state.name',width: '5%' },
  { title: 'PD', data: 'slsPD' },
  { title: 'Name', data: 'name' },
  
]  

const pdData = computed(() =>
  savedData.value
    .filter(i => i.component === 'PD')
    .map((item, index) => ({ ...item, serial: index + 1 }))
)

const slData = computed(() =>
  savedData.value
    .filter(i => i.component === 'SL')
    .map((item, index) => ({
      ...item,
      serial: index + 1
    }))
)



const savedData = ref([]);

const selectedComponent = ref('')

// Accordion state management
const accordionStates = ref({
  section1: true,  // First section expanded by default
  section2: false, // Second section collapsed by default
  section3: false  // Third section collapsed by default
})

const toggleAccordion = (section) => {
  accordionStates.value[section] = !accordionStates.value[section]
}
const fetchSavedData = async () => {
  try {
    const res = await fetch('/pd-sls-list');
    if (res.ok) {
      savedData.value = await res.json();
    } else {
      console.error('Failed to load saved data');
    }
  } catch (err) {
    console.error('Error loading saved data:', err);
  }
};
const formRows = ref([
  {
    state: '',
    pdComponent: '',
    slsPD: '',
    slsId: ''
  }
])

const states = ref([])

onMounted(async () => {
  fetchSavedData();
  
  try {
    const response = await fetch('/api/states')
    if (response.ok) {
      states.value = await response.json()
    } else {
      console.error('Failed to fetch states:', response.statusText)
    }
  } catch (error) {
    console.error('Error fetching states:', error)
  }
})

const addRow = () => {
  formRows.value.push({
    state: '',
    pdComponent: '',
    slsPD: '',
    slsId: ''
  })
}

watch(selectedComponent, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    formRows.value = [
      {
        state: '',
        pdComponent: '',
        slsPD: '',
        slsId: ''
      }
    ]
  }
})
const deleteRow = (id) => {
  if (confirm('Are you sure you want to delete this item?')) {
    router.delete(route('pd-sls.destroy', id), {
      onSuccess: () => {
        fetchSavedData()
        alert('Deleted successfully!')
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        alert('Delete failed')
      }
    })
  }
}

const submit = () => {
  const payload = {
    component: selectedComponent.value,
    comValue: [],
    status: 1
  }

  formRows.value.forEach(row => {
    payload.comValue.push({
      state: selectedComponent.value === 'PD' ? 0 : row.state,
      name: selectedComponent.value === 'PD' ? row.pdComponent : row.slsId,
      slsPD: selectedComponent.value === 'PD' ? null : row.slsPD // Send slsPD only for SL type
    })
  })

  router.post(route('pd-sls.store'), payload, {
    onSuccess: () => {
      selectedComponent.value = ''
      formRows.value = [
        { state: '', pdComponent: '', slsId: '', slsPD: '' }
      ]
      fetchSavedData()
      alert('Saved successfully!')
    },
    onError: (errors) => {
      console.error('Validation failed:', errors)
      alert('Validation failed')
    }
  })
}



</script>

<style scoped>
.accordion-collapse {
  transition: all 0.3s ease;
}

.accordion-collapse.show {
  display: block;
}

.accordion-collapse:not(.show) {
  display: none;
}

.accordion-button {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  padding: 1rem 1.25rem;
  font-size: 1rem;
  color: #212529;
  text-align: left;
  background-color: #fff;
  border: 0;
  border-radius: 0;
  overflow-anchor: none;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, border-radius 0.15s ease;
}

.accordion-button:not(.collapsed) {
  color: #0c63e4;
  background-color: #e7f1ff;
  box-shadow: inset 0 -1px 0 #dee2e6;
}

.accordion-button.collapsed {
  color: #212529;
  background-color: #fff;
  box-shadow: inset 0 -1px 0 #dee2e6;
}

.accordion-button::after {
  flex-shrink: 0;
  width: 1.25rem;
  height: 1.25rem;
  margin-left: auto;
  content: "";
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23212529'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-size: 1.25rem;
  transition: transform 0.15s ease;
}

.accordion-button:not(.collapsed)::after {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230c63e4'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
  transform: rotate(-180deg);
}

.accordion-body {
  padding: 1rem 1.25rem;
}

.accordion-item {
  background-color: #fff;
  border: 1px solid #dee2e6;
}

.accordion-item:not(:first-of-type) {
  border-top: 0;
}

.accordion-item:first-of-type {
  border-top-left-radius: 0.375rem;
  border-top-right-radius: 0.375rem;
}

.accordion-item:last-of-type {
  border-bottom-left-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
}
</style>


