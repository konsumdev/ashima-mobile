import { Injectable } from "@angular/core";


@Injectable({
    providedIn: 'root'
  })
export class UsersProvider {
    private userEmail: string;
    private empId: number;
    private accId: number;
    private cloudId: string;
    private profilePic: string;
    private empName: string;
    private compId: string;
    private ot_ent: boolean;
    private lv_ent: boolean;

    private is_aprvr: boolean;
    private timein_aprvr: boolean;
    private ot_aprvr: boolean;
    private lv_aprvr: boolean;
    private shft_aprvr: boolean;
    private mob_aprvr: boolean;
    private psa_id : any;

    constructor() {
    }

    setEmployeeDetails(
        email: string,
        empid: number,
        accid: number,
        cloudid: string,
        profile: string,
        empName: string,
        compId: string,
        ot_en: any,
        lv_en: any,
        is_ap: any,
        time_ap: any,
        ot_ap: any,
        shft_ap: any,
        mob_ap: any,
        lv_ap: any,
        psa: any
    ) {
        this.accId = accid;
        this.cloudId = cloudid;
        this.empId = empid;
        this.profilePic = profile;
        this.userEmail = email;
        this.empName = empName;
        this.compId = compId;
        this.ot_ent = ot_en;
        this.lv_ent = lv_en;
        this.is_aprvr = is_ap;
        this.timein_aprvr = time_ap;
        this.ot_aprvr = ot_ap;
        this.shft_aprvr = shft_ap;
        this.mob_aprvr = mob_ap;
        this.lv_aprvr = lv_ap;
        this.psa_id = psa;
    }

    setEmployeeApprover(
        isapprover: boolean,
        timeinAp: boolean,
        otAp: boolean,
        lvAp: boolean,
        shfAp: boolean,
        mbl: boolean
    ) {
        
    }

    setEmployeeEntitlements(
        ot: boolean,
        lv: boolean
    ) {
        
    }

    getEmployeeDetails() {
        return {
            'email'         : this.userEmail,
            'empid'         : this.empId,
            'accountid'     : this.accId,
            'cloudid'       : this.cloudId,
            'profileimg'    : this.profilePic,            
            'name'          : this.empName,
            'compId'        : this.compId,
            'ot_ent'        : this.ot_ent,
            'lv_ent'        : this.lv_ent,
            'is_aprvr'      : this.is_aprvr,
            'timein_aprvr'  : this.timein_aprvr,
            'ot_aprvr'      : this.ot_aprvr,
            'lv_aprvr'      : this.lv_aprvr,
            'shft_aprvr'    : this.shft_aprvr,
            'mob_aprvr'     : this.mob_aprvr,
            'psa_id'        : this.psa_id
        };
    }

    resetEmployee() {
        this.accId = null;
        this.cloudId = null;
        this.empId = null;
        this.profilePic = null;
        this.userEmail = null;
        this.empName = null;
        this.compId = null;
        this.ot_ent = null;
        this.lv_ent = null;
        this.is_aprvr = null;
        this.timein_aprvr = null;
        this.ot_aprvr = null;
        this.shft_aprvr = null;
        this.mob_aprvr = null;
        this.lv_aprvr = null;
        this.psa_id = null;
    }

}