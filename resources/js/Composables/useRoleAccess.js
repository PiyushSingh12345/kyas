import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useRoleAccess() {
    const page = usePage()
    
    const userTypeIds = computed(() => {
        return page.props.auth?.user_type_ids || []
    })
    
    const hasRole = (roles) => {
        if (!Array.isArray(roles)) {
            roles = [roles]
        }
        return roles.some(roleId => userTypeIds.value.includes(Number(roleId)))
    }
    
    const hasAnyRole = (roles) => {
        return hasRole(roles)
    }
    
    const hasAllRoles = (roles) => {
        if (!Array.isArray(roles)) {
            roles = [roles]
        }
        return roles.every(roleId => userTypeIds.value.includes(Number(roleId)))
    }
    
    const getUserRoles = () => {
        return userTypeIds.value
    }
    
    const isAdmin = () => {
        return hasRole(1) // KY_Admin
    }
    
    const isKYUser = () => {
        return hasRole(2) // KY User
    }
    
    const isMasterDataUser = () => {
        return hasRole(3) // Master Data User
    }
    
    const isPDViewer = () => {
        return hasRole(4) // PD Viewer
    }
    
    const isCSNAUser = () => {
        return hasRole(5) // CSNA User
    }
    
    const canAccessUserManagement = () => {
        return hasRole(1) // Only KY_Admin
    }
    
    const canAccessBudgetHeads = () => {
        return hasRole([2, 3]) // KY User, Master Data User
    }
    
    const canAccessBudgetAllocation = () => {
        return hasRole(2) // Only KY User
    }
    
    const canAccessMotherSanction = () => {
        return hasRole(2) // Only KY User
    }
    
    const canAccessDailySanction = () => {
        return hasRole(2) // Only KY User
    }
    
    const canAccessReAppropriation = () => {
        return hasRole(2) // Only KY User
    }
    
    const canAccessAnnualActionPlan = () => {
        return hasRole(2) // Only KY User
    }
    
    const canAccessReports = () => {
        return hasRole([1, 2, 4]) // KY_Admin, KY User, PD Viewer
    }
    
    const canViewReports = () => {
        return hasRole([1, 2, 4]) // KY_Admin, KY User, PD Viewer
    }
    
    const canEditData = () => {
        return hasRole([1, 2, 3]) // KY_Admin, KY User, Master Data User
    }
    
    const canDeleteData = () => {
        return hasRole([1, 2, 3]) // KY_Admin, KY User, Master Data User
    }
    
    const canCreateData = () => {
        return hasRole([1, 2, 3]) // KY_Admin, KY User, Master Data User
    }
    
    return {
        userTypeIds,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        getUserRoles,
        isAdmin,
        isKYUser,
        isMasterDataUser,
        isPDViewer,
        isCSNAUser,
        canAccessUserManagement,
        canAccessBudgetHeads,
        canAccessBudgetAllocation,
        canAccessMotherSanction,
        canAccessDailySanction,
        canAccessReAppropriation,
        canAccessAnnualActionPlan,
        canAccessReports,
        canViewReports,
        canEditData,
        canDeleteData,
        canCreateData
    }
}
