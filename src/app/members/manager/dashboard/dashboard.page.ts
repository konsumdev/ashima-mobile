import { Component, OnInit } from '@angular/core';
import { MenuController, Platform, AlertController } from '@ionic/angular';
import { AppComponent } from '../../../app.component';
import * as API_CONFIG from '../../../services/api-config';
import { ManagerDashboardProvider } from 'src/app/services/manager-dashboard/manager-dashboard';
import { Router } from '@angular/router';
import { StatusBar } from '@ionic-native/status-bar/ngx';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.page.html',
    styleUrls: ['./dashboard.page.scss'],
})
export class DashboardPage implements OnInit {
    contentType: string;
    apiUrl: API_CONFIG.API;
    baseUrl: API_CONFIG.API.BASE_URI;

    toggleWorkforce: string;
    toggleTeam: string;
    toggleEvents: string;
    toggleTodos: string;
    dashWorkforce: any;
    dashTeam: any;
    dashEvents: any;
    dashTodos: any;

    headCount: any;
    clockedIn: any;
    noShowEmp: any;
    onLeaves: any;
    earlyBirdEmp: any;
    tardyEmp: any;
    schedule: any;

    // approver check
    isApprover: any;
    timeinApp: any;
    overtimeApp: any;
    leaveApp: any;
    shiftsApp: any;
    mobileApp: any;

    todoLeave: any;
    todoTime: any;
    todoShift: any;
    todoOt: any;

    missingTsheets: any;
    missedPunch: any;

    birthdays: any;
    anniversaries: any;
    holidays: any;

    constructor(
        private menu: MenuController,
        public dashProv: ManagerDashboardProvider,
        public platform: Platform,
        public alertCtrl: AlertController,
        private statusBar: StatusBar,
        private router: Router,
        private mainApp : AppComponent
    ) {
        this.menu.enable(false, 'employeeMenu');
        this.menu.enable(true, 'managerMenu');
        this.menu.close();
        // this.mainApp.setManagerMenu();
        this.mainApp.apiSessionChecker().then(data=>{
        
            if (data) {
                let rslt: any = data;
                this.isApprover = rslt.is_approver;
                this.overtimeApp = rslt.overtime_app;
                this.timeinApp = rslt.timein_app;
                this.leaveApp = rslt.leave_app;
                this.shiftsApp = rslt.shifts_app;
                this.mobileApp = rslt.mobile_app;
            }
        });

        this.toggleWorkforce = 'ios-arrow-up';
        this.toggleTeam = 'ios-arrow-up';
        this.toggleEvents = 'ios-arrow-up';
        this.toggleTodos = 'ios-arrow-up';
        this.dashWorkforce = "1";
        this.dashTeam = "1";
        this.dashEvents = "1";
        this.dashTodos = "1";

        this.apiUrl = API_CONFIG.API.URL;
        this.baseUrl = API_CONFIG.API.BASE_URI;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }


        goto(url: any) {
            this.router.navigate([url]);
        }

    getHolidays() {
        this.dashProv.getHolidays().then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    this.holidays = rslt;
                }
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    getAnniversaries() {
        this.dashProv.anniversaries().then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    this.anniversaries = rslt.anniversaries	;
                }
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    getBirthdays() {
        this.dashProv.birthdays().then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    this.birthdays = rslt.birthdays;
                }
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    getMissPunch() {
        this.dashProv.missedPunches().then(res=>{
            if (res) {
                let rslt: any = res;
                if (rslt.result) {
                    if (rslt.missed_punches_count > 0) {
                        this.missedPunch = res;
                    }                    
                }                
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    getMissingTimesheets() {
        this.dashProv.missingTimesheets().then(res=>{
            if (res) {
                this.missingTsheets = res;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    getTodos() {
        this.dashProv.todos().then(res=>{
            console.log(res);
            let rslt: any = res;
            if (rslt) {
                this.todoLeave = rslt.count_leave;
                this.todoTime = rslt.count_timein;
                this.todoOt = rslt.count_overtime;
                this.todoShift = rslt.count_shift;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    getHeadCount() {
        this.dashProv.getHeadCount().then(res=>{
            let rslt: any = res;
            if (rslt) {
                this.headCount = rslt.head_count;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    employeesClockedIn() {
        this.dashProv.getEmployeesClockedIn().then(res=>{
            let rslt: any = res;
            if (rslt) {
                this.clockedIn = rslt.clocked_in;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    noShow() {
        this.dashProv.getNoShow().then(res=>{
            let rslt: any = res;
            if (rslt) {
                this.noShowEmp = rslt;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    outOnLeave() {
        this.dashProv.getOutOnLeave().then(res=>{
            let rslt: any = res;
            if (rslt) {
                this.onLeaves = rslt;
            }
        }).catch(err=>{
            console.log(err);
        });
    }

    earlyBirds() {
        this.dashProv.getEarlyBirds().then(res=>{
        let rslt: any = res;
        if (rslt) {
            this.earlyBirdEmp = rslt;
        }
        }).catch(err=>{
            console.log(err);
        });
    }

    tardiList() {
        this.dashProv.getTardyList().then(res=>{
        let rslt: any = res;
        if (rslt) {
            this.tardyEmp = rslt;
        }
        }).catch(err=>{
            console.log(err);
        });
    }

    getSchedule() {
        this.dashProv.getSched().then(res=>{
        let rslt: any = res;
        if (rslt) {
            this.schedule = rslt;
        }
        }).catch(err=>{
            console.log(err);
        });
    }

    offToggle(type) {
        if (type == "workforce") {
        // this.toggleState();
        if (this.dashWorkforce == '0') {
            this.toggleWorkforce = 'ios-arrow-up';
            this.dashWorkforce = '1';
        } else {
            this.toggleWorkforce = 'ios-arrow-down';
            this.dashWorkforce = '0';
        }
        } else if (type == "team") {
        if (this.dashTeam == '0') {
            this.toggleTeam = 'ios-arrow-up';
            this.dashTeam = '1';
        } else {
            this.toggleTeam = 'ios-arrow-down';
            this.dashTeam = '0';
        }
        } else if (type == "events") {
        if (this.dashEvents == '0') {
            this.toggleEvents = 'ios-arrow-up';
            this.dashEvents = '1';
        } else {
            this.toggleEvents = 'ios-arrow-down';
            this.dashEvents = '0';
        }
        } else if (type == "todos") {
        if (this.dashTodos == '0') {
            this.toggleTodos = 'ios-arrow-up';
            this.dashTodos = '1';
        } else {
            this.toggleTodos = 'ios-arrow-down';
            this.dashTodos = '0';
        }
        }
    }

    ngOnInit() {
        this.statusBar.show();
        this.menu.enable(false, 'employeeMenu');
        this.menu.enable(true, 'managerMenu');
        this.getHeadCount();
        this.employeesClockedIn();
        this.noShow();
        this.outOnLeave();
        this.earlyBirds();
        this.tardiList();
        this.getTodos();
        this.getMissingTimesheets();
        this.getMissPunch();
        this.getBirthdays();
        this.getAnniversaries();
        this.getHolidays();
    }
}
