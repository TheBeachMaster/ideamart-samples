require 'test_helper'

class ShuttleControllerTest < ActionController::TestCase
  test "should get ussd" do
    get :ussd
    assert_response :success
  end

  test "should get index" do
    get :index
    assert_response :success
  end

end
