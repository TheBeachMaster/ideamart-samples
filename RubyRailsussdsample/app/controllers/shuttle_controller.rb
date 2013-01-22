class ShuttleController < ApplicationController
  
  def ussd
   ussdreq = Ussdreq.new('http://localhost:7000/ussd/send','APP_000001','password');
   ussdin = ActiveSupport::JSON.decode(request.body)
   if ussdin["ussdOperation"] == 'mo-init' && ussdin["message"]=='123' && ussdin["sourceAddress"] == 'tel:94771122336'
      ussdreq.replyussd(ussdin["sourceAddress"],"Welcome to Shuttle Service, \nEnter checkin \"SPACE\" checkout destination and press Reply for driver details press *1 or *2 \n\nReply * to cont.")
   elsif ussdin["ussdOperation"] == 'mo-cont' && ussdin["message"]=='*'
      ussdreq.replyussd(ussdin["sourceAddress"],"(0)Pinnacle\n(1)Mega\n(2)D/P Mw\n(3)Nawam MW\n(4)Union Place\n(5)Vaxhall\n(6)Akbar\n(7)HO")

   elsif ussdin["ussdOperation"] == 'mo-cont' && (ussdin["message"]!="" && ussdin["message"]!="*1" && ussdin["message"]!="*2" && ussdin["message"]!="*3"&& ussdin["message"]!="*4"&& ussdin["message"]!="*5"&& ussdin["message"]!="*6"&& ussdin["message"]!="1" && ussdin["message"]!="2" )
     @replyMsg1=''
     @checkInNo, @checkOutNo = ussdin["message"].split(" ").map(&:to_i)

     if @checkInNo == @checkOutNo
          ussdreq.replyussd(ussdin["sourceAddress"],"Checkin and Checkout cannot be the same\n please try again.")

     elsif (@checkInNo < @checkOutNo || @checkInNo > @checkOutNo) && (@checkInNo<8 && @checkOutNo<8) && (@checkInNo>-1 && @checkOutNo>-1) #&& session[:final].to_i==3
          require 'spreadsheet'
          @checkInNo < @checkOutNo? sheet = Spreadsheet.open('sample3.xls').worksheet('Sheet1') : sheet = Spreadsheet.open('sample3.xls').worksheet('Sheet2')          
          1.upto(10) {|n| @replyMsg1+=sheet.row(n)[@checkInNo].to_s+ '-' + sheet.row(n)[@checkOutNo].to_s+' *'+ sheet.row(n)[8].to_s+' '}
          ussdreq.replyussd(ussdin["sourceAddress"],"in-out *shuttleNo\n"+@replyMsg1+"\n * to go back *1,*2 etc for shuttle info")
     else
        ussdreq.replyussd(ussdin["sourceAddress"],"Invalid Entry\n please try again.")
    end
    elsif ussdin["ussdOperation"] == 'mo-cont' && ( ussdin["message"]=='*1' || ussdin["message"]=='*2'|| ussdin["message"]=='*3'|| ussdin["message"]=='*4'|| ussdin["message"]=='*5'|| ussdin["message"]=='*6') #&& session[:final]==1
      sheet = Spreadsheet.open('sample3.xls').worksheet('Sheet1')
      @shuttle = ussdin["message"].split("*").last.to_i
      @driver=sheet.row(13+@shuttle)[2].to_s
      @tel=sheet.row(13+@shuttle)[3].to_s
      @vehi=sheet.row(13+@shuttle)[1].to_s
      ussdreq.replyussd(ussdin["sourceAddress"],"Shuttle "+@shuttle.to_s+"\n"+@driver+"\nTel: "+@tel+"\nTransID: "+@vehi+"\nReply * to go back ")
   end
  end
end