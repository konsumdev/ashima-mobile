    import { Component, OnInit } from '@angular/core';
    import * as API_CONFIG from '../../../services/api-config';
import { MenuController } from '@ionic/angular';
import { TodoProvider } from 'src/app/services/todo/todo';
import { AppComponent } from 'src/app/app.component';
import { MyCommonServices } from 'src/app/shared/myCommonServices';

@Component({
    selector: 'app-todo-leaves',
    templateUrl: './todo-leaves.page.html',
    styleUrls: ['./todo-leaves.page.scss'],
})
export class TodoLeavesPage implements OnInit {
    contentType: string;
    apiUrl: API_CONFIG.API;
    baseUrl: API_CONFIG.API.BASE_URI;

    // approver check
    isApprover: any;
    timeinApp: any;
    overtimeApp: any;
    leaveApp: any;
    shiftsApp: any;
    mobileApp: any;
    timesheets: any;
    leaves: any;
    fetching: any;
    allPage: any;
    allTotal: any;

    constructor(
        private menu: MenuController,
        public todoProv: TodoProvider,
        private mainApp : AppComponent,
        public comCtrl: MyCommonServices,
    ) {
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

        this.apiUrl = API_CONFIG.API.URL;
        this.baseUrl = API_CONFIG.API.BASE_URI;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getList() {
        this.fetching = true;
        let spnr = this.comCtrl.presentLoading('Updating list ...');
        this.todoProv.leavesList(this.allPage+'').then(res=>{
            this.comCtrl.dismissLoading(spnr);
            if (res) {
            let rslt: any = res;
            this.leaves = false;
            if (rslt.length) {    
                this.leaves = [];
                let currentDate : any = false;
                let currentList : any = [];
    
                rslt.forEach((value, index) => {
                    if (value.date_filed.split(" ")[0] != currentDate) {
                        currentDate = value.date_filed.split(" ")[0];
                        let newGroup = {
                            date: currentDate,
                            employees: []
                        };
                        currentList = newGroup.employees;
                        this.leaves.push(newGroup);
                        
                    }
                    currentList.push(value);
                });
                console.log(this.leaves);
            }
            }
            this.fetching = false;
        }).catch(err=>{
            this.comCtrl.dismissLoading(spnr);
            console.log(err);
            this.fetching = false;
        });
    }

    doInfiniteAll(infiniteScroll: any) {
        this.allPage = this.allPage+1;
        setTimeout(() => {
        this.todoProv.leavesList(this.allPage+'').then(res=>{
            //this.comCtrl.dismissLoading(spnr);
            if (res) {
            let rslt: any = res;
            this.leaves = false;
            this.allTotal = parseInt(rslt.total, 10);

            if (rslt.length) {  
                this.leaves = [];                  
                let currentDate : any = false;
                let currentList : any = [];

                rslt.forEach((value, index) => {

                    if (value.date_filed.split(" ")[0] != currentDate) {
                    
                        currentDate = value.date_filed.split(" ")[0];
                        let newGroup = {
                            date: currentDate,
                            employees: []
                        };
                        currentList = newGroup.employees;
                        this.leaves.push(newGroup);
                        
                    }
                    currentList.push(value);
                });
            }      
            }
            
            infiniteScroll.target.complete();
        }).catch(err=>{
            infiniteScroll.target.complete();
        });

        if (this.allPage == this.allTotal) {
            infiniteScroll.target.disabled = true;
        }
        }, 1000);
    }

    doRefresh(refresher: any) {
        this.allPage = "1";
        this.todoProv.leavesList(this.allPage+'').then(res=>{
            let rslt: any = res;
            this.leaves = false;

            if (rslt.length) {    
                this.leaves = [];
                let currentDate : any = false;
                let currentList : any = [];

                rslt.forEach((value, index) => {
                    if (value.date_filed.split(" ")[0] != currentDate) {
                        currentDate = value.date_filed.split(" ")[0];
                        let newGroup = {
                            date: currentDate,
                            employees: []
                        };
                        currentList = newGroup.employees;
                        this.leaves.push(newGroup);
                    }

                    currentList.push(value);
                });
                console.log(this.leaves);
            }
            
            this.fetching = false;
            refresher.target.complete();
        }).catch(err=>{
            console.log(err);
            this.fetching = false;
            refresher.target.complete();
        });
    }

    ngOnInit() {
        // this.menu.toggle();
        this.menu.enable(false, 'employeeMenu');
        this.menu.enable(true, 'managerMenu');
        this.getList();
    }
}
