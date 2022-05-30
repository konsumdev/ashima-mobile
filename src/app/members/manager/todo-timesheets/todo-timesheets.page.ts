    import { Component, OnInit } from '@angular/core';
import { MenuController } from '@ionic/angular';
import { AppComponent } from '../../../app.component';
import * as API_CONFIG from '../../../services/api-config';
import { TodoProvider } from 'src/app/services/todo/todo';
import { MyCommonServices } from 'src/app/shared/myCommonServices';

@Component({
    selector: 'app-todo-timesheets',
    templateUrl: './todo-timesheets.page.html',
    styleUrls: ['./todo-timesheets.page.scss'],
})

export class TodoTimesheetsPage implements OnInit {
    
    contentType: string;
    apiUrl: API_CONFIG.API;
    baseUrl: API_CONFIG.API.BASE_URI;
    
    // approver check
    timesheets: any;
    allPage: any;
    allTotal: any;
    fetching: any;

    constructor(
        private menu: MenuController,
        public todoProv: TodoProvider,
        private mainApp : AppComponent,
        public comCtrl: MyCommonServices,
    ) {
        // this.mainApp.setManagerMenu();
        this.mainApp.apiSessionChecker();

        this.apiUrl = API_CONFIG.API.URL;
        this.baseUrl = API_CONFIG.API.BASE_URI;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getList() {
        this.fetching = true;
        let spnr = this.comCtrl.presentLoading('Updating list ...');
        this.todoProv.timeinList(this.allPage+'').then(res=>{
            this.comCtrl.dismissLoading(spnr);
            
            let all_res: any = res;            
            this.timesheets = false;
            
            if (all_res) {   
                let rslt: any = all_res.list;                

                if (rslt) {

                    this.timesheets = [];             
                    let currentDate : any = false;
                    let currentList : any = [];

                    this.allTotal = parseInt(all_res.total, 10);
                    this.allPage = parseInt(all_res.page, 10);

                    rslt.forEach((value, index) => {

                        if (value.change_log_date_filed.split(" ")[0] != currentDate) {
                        
                            currentDate = value.date;//value.change_log_date_filed.split(" ")[0];
                            let newGroup = {
                                date: currentDate,
                                employees: []
                            };
                            currentList = newGroup.employees;
                            this.timesheets.push(newGroup);
                            
                        }
                        currentList.push(value);
                    });
                }
            }
            this.fetching = false;
            console.log(this.timesheets);
        }).catch(err=>{
            this.comCtrl.dismissLoading(spnr);
            console.log(err);
            this.fetching = false;
        });
    }

    doRefresh(refresher: any) {
        this.allPage = "1";
        this.fetching = true;
        this.todoProv.timeinList(this.allPage+'').then(res=>{
            
        let all_res: any = res;            
        this.timesheets = false;
        if (all_res) {   
                let rslt: any = all_res.list;
                
                if (rslt) {

                    this.timesheets = [];             
                    let currentDate : any = false;
                    let currentList : any = [];

                    this.allTotal = parseInt(all_res.total, 10);
                    this.allPage = parseInt(all_res.page, 10);

                    rslt.forEach((value, index) => {

                        if (value.change_log_date_filed.split(" ")[0] != currentDate) {
                        
                            currentDate = value.date;//value.change_log_date_filed.split(" ")[0];
                            let newGroup = {
                                date: currentDate,
                                employees: []
                            };
                            currentList = newGroup.employees;
                            this.timesheets.push(newGroup);
                            
                        }
                        currentList.push(value);
                    });
                }
            }
            this.fetching = false;
            refresher.target.complete();
        }).catch(err=>{
            console.log(err);
            this.fetching = false;
            refresher.target.complete();
        });        
    }

    doInfiniteAll(infiniteScroll: any) {

        this.allPage = this.allPage+1;

        setTimeout(() => {
            this.todoProv.timeinList(this.allPage+'').then( res => {
                let all_res: any = res;
                let rslt: any = all_res.list;

                this.allTotal = parseInt(all_res.total, 10);
                this.allPage = parseInt(all_res.page, 10);

                let currentDate : any = false;
                let currentList : any = [];

                if (rslt) {
                    rslt.forEach((value, index) => {

                        if (value.change_log_date_filed.split(" ")[0] != currentDate) {
                        
                            currentDate = value.date;//value.change_log_date_filed.split(" ")[0];
                            let newGroup = {
                                date: currentDate,
                                employees: []
                            };
                            currentList = newGroup.employees;
                            this.timesheets.push(newGroup);
                            
                        }
                        currentList.push(value);
                    });
                }

                infiniteScroll.target.complete();
            }).catch(error => {
            infiniteScroll.target.complete();
                console.log(error);
            });
            
            if (this.allPage == this.allTotal) {
            infiniteScroll.target.disabled = true;
            }
        }, 1000);
    }
    
    ngOnInit() {
        // this.menu.toggle();
        this.menu.enable(false, 'employeeMenu');
        this.menu.enable(true, 'managerMenu');
        this.getList();
    }
}
