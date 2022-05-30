import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class TodoProvider {
    contentType: any;
    apiUrl: any;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    /** TIMESHEET */
    timeinList(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/todo_timein_list', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    checkLocks() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/todo/check_locks', {}, {})
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    approveTimein(id: any, split: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'employee_time_in_id' : id,
            'is_split' : split,
            'approve_timein' : "1"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/approve_timein', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    rejectTimein(id: any, split: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'employee_time_in_id' : id,
            'is_split' : split,
            'reject_timein' : "1"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/reject_timein', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    /** OVERTIME */
    overtimeList(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/todo_overtime_list', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    approveOvertime(id: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'overtime_id' : id,
            'approve_overtime' : "1",
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/approve_overtime', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    rejectOvertime(id: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'overtime_id' : id,
            'reject_overtime' : "1",
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/reject_overtime', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    /** SHIFTS */
    shiftList(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/todo_shifts_list', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    approveShifts(id: any, payroll_lock_close: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'employee_work_schedule_application_id' : id,
            'payroll_lock_close' : payroll_lock_close,
            'approve_shifts' : "1",
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/approve_shifts', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    rejectShifts(id: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'employee_work_schedule_application_id' : id,
            'reject_shifts' : "1",
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/reject_shifts', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    /** MOBILE */
    mobileList(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/todo_mobile_list', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    approveMobile(id: any, clock_type: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'mobile_id' : id,
            'clock_type' : clock_type,
            'approve_clockin' : "1",
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/mobile_application', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    rejectMobile(id: string, clock_type: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'mobile_id' : id,
            'clock_type' : clock_type,
            'reject_clockin' : "1",
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/mobile_application', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    /** LEAVES */
    leavesList(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/todo_leave_list', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
        
    }

    approveLeave(id: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'leave_ids' : id,
            'approve_leave' : "1"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/approve_leave', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    rejectLeave(id: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'leave_ids' : id,
            'reject_leave' : "1"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/reject_leave', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    /** Rest Day */
    restDayList(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/todo_rest_day_list', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    /** Holiday */
    holidayList(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/todo/todo_holiday_list', postParams, headers)
            .then(data => {
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }
}
