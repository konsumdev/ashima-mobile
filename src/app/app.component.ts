import { Component } from '@angular/core';

import { Platform, MenuController, NavController } from '@ionic/angular';
import { SplashScreen } from '@ionic-native/splash-screen/ngx';
import { StatusBar } from '@ionic-native/status-bar/ngx';
import { Router } from '@angular/router';
import { UsersProvider } from './services/users-provider';
import * as API_CONFIG from './services/api-config';
import { LoginProvider } from './services/login/login';
import { MyCommonServices } from './shared/myCommonServices';

@Component({
    selector: 'app-root',
    templateUrl: 'app.component.html',
    styleUrls: ['app.component.scss']
})
export class AppComponent {

    rootPage        : any;
    isManager       : boolean = false;
    activeMenu      : any;
    employeeMenus   : any;
    showHours       : boolean = true;
    ihideHrs        : boolean = true;
    ihideTodoE      : boolean = true;
    ihideEarn       : boolean = true;
    ihideDed        : boolean = true;
    showMoreHrs     : string = 'md-add';
    showMoreEarn    : string = 'md-add';
    showMoreDed     : string = 'md-add';
    dashActiv       : boolean = true;
    leaveActiv      : boolean = true;
    activeMenuLink  : string = 'Dashboard';
    activeSMenuLink : string = '';
    menuName        : string = '';
    menuEmail       : string = '';
    menuDP          : string = '';
    empDet          : any;
    apiUrl          : any;
    compId          : any;
    empId           : any;
    accId           : any;
    directReports   : any;
    defImgLoc       : string;
    menuBG          : string = '';
    versionNum      : string = "4.0";
    managerMenus    : any;
    ihideTodo       : boolean = true;
    ihideMhours     : boolean = true;
    initMenu        : boolean = true;
    navigate : any;

    constructor(
        private platform    : Platform,
        private splashScreen: SplashScreen,
        private statusBar   : StatusBar,
        private router      : Router,
        private userDetails : UsersProvider,
        private menu        : MenuController,
        public lp           : LoginProvider,
        public comCtrl      : MyCommonServices,
        private navCtrl     : NavController
    ) {
        this.versionNum = API_CONFIG.API.VERSION;
        this.apiUrl = API_CONFIG.API.BASE_URI;
        this.isManager = false;
        // this.statusBar.styleDefault();
        this.platform.ready().then(() => {            
            this.splashScreen.hide();
        });
    }

    ngOnInit() {
        this.getEmployeeDetails();
        setTimeout(() => {
            this.statusBar.overlaysWebView(true);
        }, 500);
    }

    closeMenu() {
        this.menu.close();
    }

    getSessionDetails() {
        var details = this.userDetails.getEmployeeDetails();
        return details;
    }

    getEmployeeDetails(managerMenu?: any) {        
        this.empDet = this.userDetails.getEmployeeDetails();        
        this.menuEmail = this.empDet.email;
        this.menuName = this.empDet.name;
        this.compId = this.empDet.compId;
        this.menuDP = (this.empDet.profileimg) ? this.apiUrl + "uploads/companies/" + this.compId + "/" + this.empDet.profileimg : 'assets/imgs/default-profile-pic.png';
        this.accId = this.empDet.accountid;
        this.empId = this.empDet.empid;        
        this.defImgLoc = this.apiUrl + "uploads/companies/" + this.compId + "/";                
        this.managerChecker(this.empId, this.compId);
        this.generateEmpMenus();
        this.generateManagerMenus();
        
        // if (managerMenu) {
        //     this.mngrMenu();
        // }
    }

    generateManagerMenus() {
            
        var todoItems : any = [];
        if (this.empDet.timein_aprvr) {
            todoItems.push({
                subname: 'Timesheets',
                subref: '/members/todo-timesheets'
            });
            todoItems.push({
                subname: 'Rest Day',
                subref: '/members/todo-restday'
            });
            todoItems.push({
                subname: 'Holiday',
                subref: '/members/todo-holiday'
            });
        }
        if (this.empDet.ot_aprvr) {
            todoItems.push({
                subname: 'Overtimes',
                subref: '/members/todo-overtime'
            });
        }
        if (this.empDet.lv_aprvr) {
            todoItems.push({
                subname: 'Leaves',
                subref: '/members/todo-leaves'
            });
        }
        if (this.empDet.shft_aprvr) {
            todoItems.push({
                subname: 'Shifts',
                subref: '/members/todo-shifts'
            });
        }
        
        var man_menu: any;
        man_menu = [{
            name: 'Dashboard',
            href: '/members/manager-dashboard',
            pseudo: 'Managerdash',
            subitem: ''
        }];

        if (todoItems != 'undefined' && this.empDet.is_aprvr) {
            man_menu.push({
                name: 'To do',
                pseudo: 'Managertodo',
                subitem: todoItems
            });
        }

        man_menu.push({
            name: 'Workforce',
            href: '/members/directory',
            pseudo: 'Workforce',
            subitem: ''
        },
        {
            name: 'Leaves',
            href: '/members/manager-leaves',
            pseudo: 'Managerleave',
            subitem: ''
        });

        var man_hr_menu : any = [{
            subname: 'Timesheets',
            subref: '/members/manager-timesheets'
        },
        {
            subname: 'Overtime',
            subref: '/members/manager-overtime'
        }];

        man_menu.push({
            name: 'Hours',
            pseudo: 'Managerhrs',
            subitem: man_hr_menu
        });

        this.managerMenus = man_menu;
    }

    generateEmpMenus() {
            
        if (!this.isManager && !this.initMenu && this.empDet.is_aprvr) {
            
            var todoItems : any = [];
            
            if (this.empDet.timein_aprvr) {
                todoItems.push({
                    subname: 'Timesheets',
                    subref: 'TodoTime'
                });
                todoItems.push({
                    subname: 'Rest Day',
                    subref: 'TodoRestDay'
                });
                todoItems.push({
                    subname: 'Holiday',
                    subref: 'TodoHoliday'
                });
            }
            if (this.empDet.ot_aprvr) {
                todoItems.push({
                    subname: 'Overtimes',
                    subref: 'TodoOt'
                });
            }
            if (this.empDet.lv_aprvr) {
                todoItems.push({
                    subname: 'Leaves',
                    subref: 'TodoLeave'
                });
            }
            if (this.empDet.shft_aprvr) {
                todoItems.push({
                    subname: 'Shifts',
                    subref: 'TodoShift'
                });
            }
            if (this.empDet.mob_aprvr) {
                todoItems.push({
                    subname: 'Mobile Clock ins',
                    subref: 'TodoMobile'
                });
            }
        }
        

        var itemsubs = [{
            subname: 'Clock In',
            subref: '/members/clockin'
        },
        {
            subname: 'Timesheets',
            subref: '/members/timesheets'
        }];
                
        if (this.empDet.ot_ent == "1") {
            itemsubs.push({
                subname: 'Overtime',
                subref: '/members/overtime'
            });
        }
        var men_u: any;
        men_u = [{
            name: 'Dashboard',
            href: '/members/dashboard',
            close: '',
            hideClass: 'ihideDash',
            subitem: ''
        }];

        if (!this.isManager && !this.initMenu && this.empDet.is_aprvr) {
            if (todoItems != 'undefined') {
                men_u.push({
                    name: 'To do',
                    close: 'disable',
                    hideClass: 'ihideTodoE',
                    subitem: todoItems
                });
            }
        }

        if (this.empDet.lv_ent == "1") {
            men_u.push({
                name: 'Leaves',
                href: '/members/leaves',
                close: '',
                hideClass: 'ihideLvs',
                subitem: ''
            });
        }

        men_u.push({
            name: 'Hours',
            close: 'disable',
            hideClass: 'ihideHrs',
            subitem: itemsubs
        },
        {
            name: 'Paycheck',
            href: '/members/payslip',
            close: '',
            hideClass: 'ihidePay',
            subitem: ''
        },
        {
            name: 'Earnings',
            close: 'disable',
            hideClass: 'ihideEarn',
            subitem: [
                {
                    subname: 'Allowances',
                    subref: '/members/allowances'
                },
                {
                    subname: 'Commissions',
                    subref: '/members/commissions'
                },
                {
                    subname: 'De Minimis',
                    subref: '/members/deminimis'
                }
            ]
        },
        {
            name: 'Deductions',
            close: 'disable',
            hideClass: 'ihideDed',
            subitem: [
                {
                    subname: 'Contributions',
                    subref: '/members/contributions'
                },
                {
                    subname: 'Withholding Tax',
                    subref: '/members/withholdingtax'
                },
                {
                    subname: 'Government Loans',
                    subref: '/members/govloans'
                },
                {
                    subname: '3rd Party Loans',
                    subref: '/members/thirdloans'
                },
                {
                    subname: 'Other Deductions',
                    subref: '/members/otherdeductions'
                }
            ]
        },
        {
            name: 'Shifts',
            href: '/members/shift',
            close: '',
            hideClass: 'ihideShft',
            subitem: ''
        },
        {
            name: 'Documents',
            href: '/members/docu',
            close: '',
            hideClass: 'ihideDocs',
            subitem: ''
        },
        {
            name: 'Profile',
            href: '/members/profile',
            close: '',
            hideClass: 'ihideProf',
            subitem: ''
        },
        {
            name: 'QR Code',
            href: '/members/employee-qr',
            close: '',
            hideClass: 'ihideProf',
            subitem: ''
        });
        
        this.employeeMenus = men_u;
    }

    getManagerCheck() {
        return this.isManager;
    }

    setManagerCheck() {
        this.isManager = true;
    }

    empMenuActive() {
        // console.log("switch to employee");
        this.router.navigate(['members', 'dashboard']);
        // this.activeMenu = 'employeeMenu';
        // this.menu.enable(true, 'employeeMenu');
        // this.menu.enable(false, 'managerMenu');
        // this.menu.open('employeeMenu');
        // this.ihideTodo = true;
        // this.ihideMhours = true;

        // this.rootPage = 'EmpDashboardPage';
    }

    isGroupHidden(group) {
        if (group == 'Hours') {
            if (this.ihideHrs) {
                return true;
            }
            return false;
        }
        else if (group == 'Earnings') {
            if (this.ihideEarn) {
                return true;
            }
            return false;
        }
        else if (group == 'Deductions') {
            if (this.ihideDed) {
                return true;
            }
            return false;
        }
        else if (group == 'Managertodo') {
            if (this.ihideTodo) {
                return true;
            }
            return false;
        }
        else if (group == 'Managerhrs') {
            if (this.ihideMhours) {
                return true;
            }
            return false;
        }
        else if (group == 'To do') {
            if (this.ihideTodoE) {
                return true;
            }
            return false;
        } else {
            return true;
        }
    }

    toggleSubGroup(group) {
        this.activeSMenuLink = group;
        this.goToPage(group);
    }

    toggleManagerSubGroup(group) {
        this.activeSMenuLink = group;
        this.goToManagerPage(group);
    }

    managerToggleGroup(group) {
        this.activeMenuLink = group;
        
        if (group == 'Hours') {
            if (this.ihideHrs) {
                this.ihideHrs = false;
            } else {
                this.ihideHrs = true;
            }
            this.ihideEarn = true;
            this.ihideDed = true;
        } else if (group == 'Managertodo') {
            if (this.ihideTodo) {
                this.ihideTodo = false;
            } else {
                this.ihideTodo = true;
            }
            this.ihideMhours = true;
            this.ihideHrs = true;
            this.ihideEarn = true;
            this.ihideDed = true;
        } else if (group == 'Managerhrs') {
            if (this.ihideMhours) {
                this.ihideMhours = false;
            } else {
                this.ihideMhours = true;
            }
            this.ihideTodo = true;
            this.ihideHrs = true;
            this.ihideEarn = true;
            this.ihideDed = true;
        } else {
            this.ihideHrs = true;
            this.ihideEarn = true;
            this.ihideDed = true;
            this.ihideTodo = true;
            this.ihideMhours = true;
        }

        this.goToManagerPage(group);
    }

    toggleGroup(group) {
        this.activeMenuLink = group;

        this.ihideHrs = true;
        this.ihideEarn = true;
        this.ihideDed = true;
        this.ihideTodo = true;
        this.ihideMhours = true;
        this.ihideTodoE = true;
        
        if (group == 'Hours') {
            if (this.ihideHrs) {
                this.ihideHrs = false;
            } else {
                this.ihideHrs = true;
            }
        } else if (group == 'Earnings') {
            if (this.ihideEarn) {
                this.ihideEarn = false;
            } else {
                this.ihideEarn = true;
            }
        } else if (group == 'Deductions') {
            if (this.ihideDed) {
                this.ihideDed = false;
            } else {
                this.ihideDed = true;
            }
        } else if (group == 'Managertodo') {
            if (this.ihideTodo) {
                this.ihideTodo = false;
            } else {
                this.ihideTodo = true;
            }
        } else if (group == 'Managerhrs') {
            if (this.ihideMhours) {
                this.ihideMhours = false;
            } else {
                this.ihideMhours = true;
            }
        } else if (group == 'To do') {
            if (this.ihideTodoE) {
                this.ihideTodoE = false;
            } else {
                this.ihideTodoE = true;
            }
        } else {
            this.ihideHrs = true;
            this.ihideEarn = true;
            this.ihideDed = true;
            this.ihideTodo = true;
            this.ihideMhours = true;
            this.ihideTodoE = true;
        }

        this.goToPage(group);
    }

    goToPage(whatPage: any) {

    }

    goToManagerPage(whatPage: any) {

    }

    setManagerMenu() {
        this.activeMenu = 'managerMenu';
        this.menu.enable(true, 'managerMenu');
        this.menu.enable(false, 'employeeMenu');
        this.menu.open('managerMenu');
    }

    mngrMenu() {
        console.log("switch to manager");
        this.router.navigate(['members', 'manager-dashboard']);
        if (!this.isManager) {
            // this.empMenuActive();
        }

        // this.setManagerMenu();
        
        // this.ihideHrs = true;
        // this.ihideEarn = true;
        // this.ihideDed = true;

        // this.rootPage = 'MngrDashboardPage';
    }

    logoutUser(force?: boolean) {
        this.activeMenuLink = 'Dashboard';
        this.activeSMenuLink = '';
        this.ihideHrs = true;
        this.ihideEarn = true;
        this.ihideDed = true;
        this.isManager = false;
        
        // this.appCtrl.getRootNav().setRoot('LoginPage');
        this.navCtrl.setDirection('root');
        this.router.navigate(['/login'])

        this.lp.logoutUser().then( res => {
        if (force) {
            this.comCtrl.presentToast("Your session has ended. Please sign in again.", "success");
        } else {
            this.comCtrl.presentToast("You have logged out.", "success");
        }
            
        }).catch(error => {
            console.log(error);
        });
    }

    managerChecker(emp_id: any, comp_id: any) {
        this.lp.checkManager(emp_id, comp_id).then( res => {            
            let mngrRes : any;
            mngrRes = res;
            
            if (mngrRes.result == 1) {                                
                this.isManager = true;
                this.directReports = mngrRes.employees;
            }
            // this.initMenu = false;
            // this.generateEmpMenus();
            // this.generateManagerMenus();
        }).catch(error => {
            // this.initMenu = false;
            // this.generateEmpMenus();
            // this.generateManagerMenus();
            console.log(error);
        });    
    }

    apiSessionChecker(managerNi?: any) {

        return new Promise((resolve) => {
            this.lp.checkSessionData().then( res => {
                
                let sessionRes : any;
                sessionRes = res;
                console.log(sessionRes)
                if (sessionRes.result == 1) {
                    this.userDetails.setEmployeeDetails(
                        sessionRes.session.email,
                        sessionRes.session.emp_id,
                        sessionRes.session.account_id,
                        sessionRes.session.cloud_id,
                        sessionRes.session.profile,
                        sessionRes.session.fname + ' ' + sessionRes.session.lname,
                        sessionRes.session.comp_id,
                        sessionRes.session.entitle_ot,
                        sessionRes.session.entitle_lv,
                        sessionRes.session.is_approver,
                        sessionRes.session.timein_app,
                        sessionRes.session.overtime_app,
                        sessionRes.session.shifts_app,
                        sessionRes.session.mobile_app,
                        sessionRes.session.leave_app,
                        sessionRes.session.psa_id
                    );
                    this.getEmployeeDetails(managerNi);
                    resolve(sessionRes.session);
                } else {
                    // this.logoutUser(true);
                }
            }).catch(error => {
                console.log(error);
            });
        });
    }

    mngrSessionChecker() {

        return new Promise(() => {
            this.lp.checkSessionData().then( res => {
                
                let sessionRes : any;
                sessionRes = res;

                if (sessionRes.result == 1) {
                    this.userDetails.setEmployeeDetails(
                        sessionRes.session.email,
                        sessionRes.session.emp_id,
                        sessionRes.session.account_id,
                        sessionRes.session.cloud_id,
                        sessionRes.session.profile,
                        sessionRes.session.fname + ' ' + sessionRes.session.lname,
                        sessionRes.session.comp_id,
                        sessionRes.session.entitle_ot,
                        sessionRes.session.entitle_lv,
                        sessionRes.session.is_approver,
                        sessionRes.session.timein_app,
                        sessionRes.session.overtime_app,
                        sessionRes.session.shifts_app,
                        sessionRes.session.mobile_app,
                        sessionRes.session.leave_app,
                        sessionRes.session.psa_id
                    );
                    this.getEmployeeDetails();
                    return true;
                } else {
                    this.logoutUser(true);
                }
            }).catch(error => {
                console.log(error);
            });
        });
    }

}
