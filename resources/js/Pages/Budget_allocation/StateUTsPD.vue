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
              <div class="card">
                <div class="card-header">
                  <div class="card-title">State-wise list of PD/Component and SLS</div>
                </div>
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
                  <div class="bg-primary text-white px-3 py-2 rounded mt-4">
                    <h5 class="mb-0">PD  Component  List</h5>
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
                <div class="bg-primary text-white px-3 py-2 rounded mt-4">
                    <h5 class="mb-0">SL Component  List</h5>
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


                </div> <!-- card-body -->
              </div> <!-- card -->
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
import { ref, watch, onMounted,computed } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net-bs5'
import 'bootstrap/dist/css/bootstrap.css'
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
onMounted(() => {
  fetchSavedData();
});

onMounted(async () => {
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
      alert('Saved successfully!')
    },
    onError: (errors) => {
      console.error('Validation failed:', errors)
      alert('Validation failed')
    }
  })
}



</script>


